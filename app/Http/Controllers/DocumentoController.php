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
        $perPage = 20; // puedes cambiarlo

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

        // detectar encabezados
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

        // fallback posicional
        if (!isset($colIndex['numero_doc'])) {
            $colIndex = ['tipo_doc' => 'A', 'numero_doc' => 'B', 'nombre' => 'C'];
        }

        $generator = new BarcodeGeneratorPNG();

        $documentosSesion = session('documentos_temporales', []);
        $erroresPorFila = [];
        $totalGuardados = 0;
        $totalIgnoradosVacios = 0;

        foreach ($rows as $i => $row) {
            if ($i == 1) continue; // encabezado

            $tipo   = trim($row[$colIndex['tipo_doc']] ?? '');
            $numero = trim($row[$colIndex['numero_doc']] ?? '');
            $nombre = trim($row[$colIndex['nombre']] ?? '');

            // si la fila está completamente vacía
            if ($tipo === '' && $numero === '' && $nombre === '') {
                $totalIgnoradosVacios++;
                continue;
            }

            // número vacío → error, no se guarda
            if ($numero === '') {
                $erroresPorFila[$i] = "Fila {$i}: número vacío.";
                continue;
            }

            // generar barcode
            try {
                $barcodeData   = $generator->getBarcode($numero, $generator::TYPE_CODE_128);
                $barcodeBase64 = base64_encode($barcodeData);
            } catch (\Throwable $t) {
                $erroresPorFila[$i] = "Fila {$i}: error generando código.";
                Log::error("Fila {$i} - error barcode: " . $t->getMessage());
                continue;
            }

            // agregar SIEMPRE (sin duplicados)
            $documentosSesion[] = [
                'tipo_doc'       => $tipo,
                'numero_doc'     => $numero,
                'nombre'         => $nombre,
                'barcode_base64' => $barcodeBase64,
                'created_at'     => Carbon::now()->format('Y-m-d H:i'),
            ];

            $totalGuardados++;
        }

        // crear mensajes
        $messages = [];
        $messages[] = "Importación completada. Guardados (en sesión): {$totalGuardados}.";
        if ($totalIgnoradosVacios > 0)
            $messages[] = "Ignorados (vacíos): {$totalIgnoradosVacios}.";

        // Nombre de archivo exportado
        $nombreExportado = $request->nombre_exportado
            ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        session([
            'documentos_temporales' => $documentosSesion,
            'nombre_archivo_exportado' => $nombreExportado
        ]);

        session()->flash('success', $messages);
        session()->flash('import_result', [
            'summary' => $messages,
            'errors' => $erroresPorFila,
            'saved' => $totalGuardados
        ]);

        return redirect()->route('documentos.index');

    } catch (\Exception $e) {
        Log::error("Error importación (memoria): " . $e->getMessage());
        return redirect()->back()
            ->with('error', 'Error importando archivo: ' . $e->getMessage());
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

            // limpiar sesión (no queda nada)
            session()->forget('documentos_temporales');

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
        return redirect()->route('documentos.index')->with('success', 'Vista limpiada. Datos temporales eliminados.');
    }
}