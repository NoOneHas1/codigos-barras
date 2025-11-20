<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentoController extends Controller
{

    // LISTAR: muestra documentos temporales desde la sesión
    public function index(Request $request)
    {
        $documentos = session('documentos_temporales', []);

        // página actual
        $page = $request->input('page', 1);

        // cuántos documentos por página
        $perPage = 20;  

        // calcular colección paginada
        $itemsPaginated = array_slice($documentos, ($page - 1) * $perPage, $perPage);

        // crear paginador
        $paginador = new LengthAwarePaginator(
            $itemsPaginated,
            count($documentos),
            $perPage,
            $page,
            ['path' => route('documentos.index')]
        );

        return view('documentos.index', [
            'documentos' => $paginador
        ]);
    }

// IMPORTAR: procesa Excel, genera barcode en memoria (base64) y guarda solo en sesión
public function importarExcel(Request $request)
{
    Log::info("Importación (memoria) iniciada");

    $request->validate([
        'archivo' => 'required|file|mimes:xlsx,xls,csv,xlsm',
    ]);

    try {

        $file = $request->file('archivo');
        $fullPath = $file->getRealPath();

        $spreadsheet = IOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return redirect()->back()->with('error', 'El archivo no contiene filas de datos.');
        }

        // ===========================
        // Detectar encabezados
        // ===========================
        $headerRow = $rows[1];
        $normalized = [];

        foreach ($headerRow as $col => $val) {
            $normalized[$col] = strtolower(trim(str_replace([' ', '_', '-', '.'], '', (string)$val)));
        }

        $alias = [
            'tipo_doc'   => ['tipodoc','tipodocumento','tipo','tipodoc','tipodocument'],
            'numero_doc' => ['numerodoc','numerodocumento','numero','documento','dni','docnumber','number'],
            'nombre'     => ['nombre','nombres','name','fullname','nombrecompleto']
        ];

        $colIndex = [];
        foreach ($alias as $field => $aliasList) {
            foreach ($normalized as $col => $val) {
                if (in_array($val, $aliasList)) {
                    $colIndex[$field] = $col;
                    break;
                }
            }
        }
        // columnas requeridas
        $columnasRequeridas = ['tipo_doc', 'numero_doc', 'nombre'];

        // verificar que se hayan detectado
        foreach ($columnasRequeridas as $campo) {
            if (!isset($colIndex[$campo])) {
                return redirect()->back()->with(
                    'error',
                    "El archivo no tiene la columna requerida: {$campo}. Verifique que las columnas sean tipo_doc, numero_doc, nombre (o sus equivalentes)."
                );
            }
        }
        // fallback posicional
        if (!isset($colIndex['numero_doc'])) {
            $colIndex = ['tipo_doc' => 'A', 'numero_doc' => 'B', 'nombre' => 'C'];
        }

        $generator = new BarcodeGeneratorPNG();

        // ======================================================
        // Obtener documentos existentes EN SESIÓN
        // ======================================================
        $documentosSesion = session('documentos_temporales', []);

        // limpiar marcas antiguas para que NO sigan verdes
        foreach ($documentosSesion as &$doc) {
            unset($doc['nuevo']);
        }
        unset($doc);

        // Para detectar si un número ya estaba antes
        $numerosExistentes = array_column($documentosSesion, 'numero_doc');

        $erroresPorFila = [];
        $totalGuardados = 0;
        $totalIgnoradosVacios = 0;

        //almacenar solo los realmente nuevos
        $nuevosDocumentos = [];

        foreach ($rows as $i => $row) {
            if ($i == 1) continue;

            $tipo   = trim($row[$colIndex['tipo_doc']] ?? '');
            $numero = trim($row[$colIndex['numero_doc']] ?? '');
            $nombre = trim($row[$colIndex['nombre']] ?? '');

            if ($tipo === '' && $numero === '' && $nombre === '') {
                $totalIgnoradosVacios++;
                continue;
            }

            if ($numero === '') {
                $erroresPorFila[$i] = "Fila {$i}: número vacío.";
                continue;
            }

            try {
                $barcodeData   = $generator->getBarcode($numero, $generator::TYPE_CODE_128);
                $barcodeBase64 = base64_encode($barcodeData);
            } catch (\Throwable $t) {
                $erroresPorFila[$i] = "Fila {$i}: error generando código.";
                continue;
            }


            // ======================================================
            // Detectar si es realmente nuevo
            // ======================================================
            $esNuevo = !in_array($numero, $numerosExistentes);

            $nuevoRegistro = [
                'tipo_doc'       => $tipo,
                'numero_doc'     => $numero,
                'nombre'         => $nombre,
                'barcode_base64' => $barcodeBase64,
                'created_at'     => Carbon::now()->format('Y-m-d H:i'),
                'nuevo'          => $esNuevo, 
            ];

            $documentosSesion[] = $nuevoRegistro;

            if ($esNuevo) {
                $nuevosDocumentos[] = $numero; // registrar solo nuevos
            }

            $totalGuardados++;
        }

        // ===========================
        // Mensajes
        // ===========================
        $messages = [];
        $messages[] = "Importación completada: {$totalGuardados} documentos agregados";
        if ($totalIgnoradosVacios > 0)
            $messages[] = "Ignorados (vacíos): {$totalIgnoradosVacios}.";

        $nombreExportado = $request->nombre_exportado
            ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        session([
            'documentos_temporales'      => $documentosSesion,
            'nombre_archivo_exportado'   => $nombreExportado,
        ]);

        // activar marcado temporal
        session()->flash('nuevos_documentos', true);

        // Flash
        session()->flash('success', $messages);
        session()->flash('import_result', [
            'summary' => $messages,
            'errors'  => $erroresPorFila,
            'saved'   => $totalGuardados,
            'nuevos'  => $nuevosDocumentos,
        ]);

        return redirect()->route('documentos.index');

    } catch (\Exception $e) {

        Log::error("Error importación (memoria): " . $e->getMessage());
        return redirect()->back()->with('error', 'Error importando archivo: ' . $e->getMessage());
    }
}


    // EXPORTAR: lee la sesión, crea Excel y devuelve descarga. Luego borra la sesión.
    public function exportarExcel(Request $request)
    {
        $documentos = session('documentos_temporales', []);

        if (empty($documentos)) {
            return redirect()->back()->with('error', 'No hay documentos cargados para exportar.');
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray(['ID', 'Tipo Documento', 'Número', 'Nombre', 'Código', 'Fecha'], null, 'A1');
            $sheet->getStyle('A1:F1')->getFont()->setBold(true);

            // Columnas
            $sheet->getColumnDimension('A')->setWidth(8);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(22);
            $sheet->getColumnDimension('D')->setWidth(30);
            $sheet->getColumnDimension('E')->setWidth(40);
            $sheet->getColumnDimension('F')->setWidth(20);

            $fila = 2;
            foreach ($documentos as $idx => $doc) {
                $sheet->setCellValue("A{$fila}", $idx + 1);
                $sheet->setCellValue("B{$fila}", $doc['tipo_doc']);
                $sheet->setCellValue("C{$fila}", $doc['numero_doc']);
                $sheet->setCellValue("D{$fila}", $doc['nombre']);
                $sheet->setCellValue("F{$fila}", $doc['created_at']);

                $sheet->getRowDimension($fila)->setRowHeight(50);

                // crear archivo temporal PNG desde base64 para que PhpSpreadsheet lo inserte
                $pngData = base64_decode($doc['barcode_base64']);
                $tmpPng = tempnam(sys_get_temp_dir(), 'barcode_') . '.png';
                file_put_contents($tmpPng, $pngData);

                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setPath($tmpPng);
                $drawing->setWidth(110);
                $drawing->setHeight(28);
                $drawing->setOffsetX(3);
                $drawing->setOffsetY(18);
                $drawing->setCoordinates("E{$fila}");
                $drawing->setWorksheet($sheet);

                // guardamos temp paths para eliminar luego
                $tempFiles[] = $tmpPng;

                $fila++;
            }

            // nombre archivo
            $nombre = session('nombre_archivo_exportado', 'documentos_exportados');
            $fileName = $nombre . "_" . date('Ymd_His') . ".xlsx";
            $filePath = tempnam(sys_get_temp_dir(), 'documentos_') . '.xlsx';

            (new Xlsx($spreadsheet))->save($filePath);

            // eliminar archivos temporales de códigos de barras
            if (!empty($tempFiles)) {
                foreach ($tempFiles as $f) {
                    if (file_exists($f)) @unlink($f);
                }
            }

            // enviar descarga y borrar el xlsx despues de enviarlo
            return response()->download($filePath, $fileName)->deleteFileAfterSend(true);

        } catch (\Throwable $t) {
            Log::error("Error exportando Excel (memoria): " . $t->getMessage());
            return redirect()->back()->with('error', 'Error generando Excel: ' . $t->getMessage());
        }
    }

    // LIMPIAR sesión (vía POST desde el botón limpiar)
    public function limpiar(Request $request)
    {
        session()->forget('documentos_temporales');
        return redirect()->route('documentos.index')->with('success', 'Vista limpiada. Datos eliminados.');
    }
}