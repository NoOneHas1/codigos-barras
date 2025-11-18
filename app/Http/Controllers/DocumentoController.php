<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Documento;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentoController extends Controller
{
    public function index(Request $request)
    {
        $lote = $request->get('lote');

        $documentos = [];

        if (!empty($lote)) {
            $documentos = Documento::where('lote_id', $lote)->get();
        }

        $lotes = Documento::select('lote_id')
                            ->distinct()
                            ->orderBy('lote_id', 'asc')
                            ->pluck('lote_id');

        return view('documentos.index', compact('documentos', 'lote', 'lotes'));
    }

    // ============================
    // IMPORTAR EXCEL (crear lote o agregar a existente)
    // ============================
    public function importarExcel(Request $request)
    {
        Log::info("=== INICIO IMPORTACIÓN ===");

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
            'lote' => 'required|string|max:255',
        ]);

        $lote = $request->lote;

        // El nombre del lote viene desde el modal y SIEMPRE existe
        $loteId = $request->lote;
        Log::info("Lote asignado: {$loteId}");

        $tempPath = null;

        try {
            $file = $request->file('archivo');
            $validExt = ['xlsx', 'xls', 'csv', 'xlsm'];
            $ext = strtolower($file->getClientOriginalExtension());

            if (!in_array($ext, $validExt)) {
                throw new \Exception("Formato no permitido. Usa archivos Excel válidos.");
            }

            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $tempPath = $file->storeAs('temp', $filename);
            $fullPath = Storage::path($tempPath);

            // leer excel
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                throw new \Exception("El archivo está vacío o no contiene filas de datos.");
            }

            // detectar encabezados (flexible)
            $headerRow = $rows[1];
            $normalized = [];
            foreach ($headerRow as $col => $val) {
                $normalized[$col] = strtolower(trim(str_replace([' ', '_', '-', '.'], '', (string)$val)));
            }

            $alias = [
                'tipo_doc' => ['tipodoc','tipodocumento','tipo','tipodoc','tipodocument'],
                'numero_doc' => ['numerodoc','numerodocumento','numero','documento','dni','docnumber','number'],
                'nombre' => ['nombre','nombres','name','fullname','nombrecompleto']
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

            // fallback posicional A/B/C si no detecta
            if (!isset($colIndex['numero_doc'])) {
                Log::info("No detectó 'numero_doc' en encabezado. Usando posiciones A/B/C.");
                $colIndex = ['tipo_doc' => 'A', 'numero_doc' => 'B', 'nombre' => 'C'];
            }

            // preparar proceso
            $generator = new BarcodeGeneratorPNG();

            $totalGuardados = 0;
            $totalIgnoradosVacios = 0;
            $totalIgnoradosDuplicado = 0;
            $erroresPorFila = [];
            $duplicadosInternos = [];
            $duplicadosEnBD = [];
            $numerosVistos = [];

            foreach ($rows as $i => $row) {
                if ($i == 1) continue; // encabezado

                $tipo = trim($row[$colIndex['tipo_doc']] ?? '');
                $numero = trim($row[$colIndex['numero_doc']] ?? '');
                $nombre = trim($row[$colIndex['nombre']] ?? '');

                // fila vacía
                if ($tipo === '' && $numero === '' && $nombre === '') {
                    $totalIgnoradosVacios++;
                    continue;
                }

                if ($numero === '') {
                    $erroresPorFila[$i] = "Fila {$i}: número vacío.";
                    continue;
                }

                // duplicado dentro del archivo
                if (in_array($numero, $numerosVistos)) {
                    $totalIgnoradosDuplicado++;
                    $duplicadosInternos[] = $numero;
                    continue;
                }
                $numerosVistos[] = $numero;

                // duplicado en BD
                if (Documento::where('numero_doc', $numero)->exists()) {
                    $totalIgnoradosDuplicado++;
                    $duplicadosEnBD[] = $numero;
                    continue;
                }

                // generar barcode
                try {
                    $barcodeData = $generator->getBarcode($numero, $generator::TYPE_CODE_128);
                } catch (\Throwable $t) {
                    $erroresPorFila[$i] = "Fila {$i}: error generando código.";
                    Log::error("Fila {$i} - error barcode: " . $t->getMessage());
                    continue;
                }

                $barcodeFile = 'codigos_barras/barcode_' . uniqid() . '.png';
                Storage::disk('public')->put($barcodeFile, $barcodeData);

                // guardar
                try {
                    Documento::create([
                        'tipo_doc' => $tipo,
                        'numero_doc' => $numero,
                        'nombre' => $nombre,
                        'codigo_path' => $barcodeFile,
                        'lote_id' => $loteId,
                    ]);
                    $totalGuardados++;
                } catch (\Throwable $t) {
                    $erroresPorFila[$i] = "Fila {$i}: error guardando en BD.";
                    Log::error("Fila {$i} - error DB: " . $t->getMessage());
                    // borrar imagen si hubo error
                    try { Storage::disk('public')->delete($barcodeFile); } catch (\Throwable $_) {}
                }
            }

            if ($tempPath) Storage::delete($tempPath);

            // preparar mensajes
            $messages = [];
            $messages[] = "Importación completada. Guardados: {$totalGuardados}.";
            if ($totalIgnoradosVacios > 0) $messages[] = "Ignorados (vacíos): {$totalIgnoradosVacios}.";
            if ($totalIgnoradosDuplicado > 0) $messages[] = "Ignorados (duplicados): {$totalIgnoradosDuplicado}.";

            $flash = [
                'summary' => $messages,
                'errors' => $erroresPorFila,
                'warnings' => [
                    'duplicados_archivo' => array_values(array_unique($duplicadosInternos)),
                    'duplicados_bd' => array_values(array_unique($duplicadosEnBD))
                ],
                'lote' => $loteId,
                'saved' => $totalGuardados,
                'ignored' => $totalIgnoradosDuplicado + $totalIgnoradosVacios
            ];

            Log::info("Resumen importación: " . implode(' | ', $messages));

        return redirect()->to(route('documentos.index') . '?lote=' . urlencode($loteId))
            ->with('success', implode(' ', $messages))
            ->with('import_result', $flash);


        } catch (\Exception $e) {
            Log::error("Error importación: " . $e->getMessage());
            if ($tempPath) Storage::delete($tempPath);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    // ============================
    // EXPORTAR LOTE
    // ============================
    public function exportarExcel(Request $request)
    {
        $request->validate([
            'lote_id' => 'required'
        ]);

        // el usuario NO envía un ID real, envía el nombre del lote
        $nombreLote = $request->lote_id;

        // buscar documentos por el nombre del lote
        $documentos = Documento::where('lote_id', $nombreLote)->get();

        if ($documentos->isEmpty()) {
            return redirect()->back()->with('error', 'No hay documentos para exportar en este lote.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['ID', 'Tipo Documento', 'Número', 'Nombre', 'Código GS1-128', 'Fecha'], null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        // Columnas
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->getColumnDimension('F')->setWidth(20);

        $fila = 2;
        foreach ($documentos as $doc) {
            $sheet->setCellValue("A{$fila}", $doc->id);
            $sheet->setCellValue("B{$fila}", $doc->tipo_doc);
            $sheet->setCellValue("C{$fila}", $doc->numero_doc);
            $sheet->setCellValue("D{$fila}", $doc->nombre);
            $sheet->setCellValue("F{$fila}", $doc->created_at->format('Y-m-d H:i'));

            $sheet->getRowDimension($fila)->setRowHeight(50);

            $imagePath = storage_path('app/public/' . $doc->codigo_path);
            if (file_exists($imagePath)) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setPath($imagePath);
                $drawing->setWidth(110);
                $drawing->setHeight(28);
                $drawing->setOffsetX(3);
                $drawing->setOffsetY(18);
                $drawing->setCoordinates("E{$fila}");
                $drawing->setWorksheet($sheet);
            }

            $fila++;
        }

        // aquí usamos el nombre del lote para crear el archivo
        $fileName = "documentos_lote_" . str_replace(' ', '_', $nombreLote) . "_" . date('Ymd_His') . ".xlsx";
        $filePath = storage_path("app/public/{$fileName}");

        (new Xlsx($spreadsheet))->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
    // ============================
    // EDITAR (RENOMBRAR) LOTE
    // - recibe 'lote_old' y 'lote_new'
    // - reemplaza lote_id para todos los documentos
    // ============================
    public function editarLote(Request $request)
    {
        $request->validate([
            'lote_old' => 'required',
            'lote_new' => 'required'
        ]);

        $old = $request->lote_old;
        $new = $request->lote_new;

        try {
            $count = Documento::where('lote_id', $old)->update(['lote_id' => $new]);
            Log::info("Lote renombrado: {$old} -> {$new}. Registros actualizados: {$count}");

            return redirect()->route('documentos.index', ['lote' => $new])
                ->with('success', "Lote renombrado correctamente. Registros actualizados: {$count}");
        } catch (\Throwable $t) {
            Log::error("Error renombrando lote: " . $t->getMessage());
            return redirect()->back()->with('error', 'Error renombrando lote: ' . $t->getMessage());
        }
    }

    // ============================
    // ELIMINAR LOTE COMPLETO
    // - borra imágenes asociadas y filas en BD
    // ============================
        public function eliminarLote(Request $request)
    {
        $request->validate([
            'lote' => 'required|string'
        ]);

        $lote = $request->lote;

        $documentos = Documento::where('lote_id', $lote)->get();

        if ($documentos->isEmpty()) {
            return redirect()->back()->with('error', 'Este lote no tiene documentos.');
        }

        // Eliminar códigos de barras
        foreach ($documentos as $doc) {
            if ($doc->codigo_path && Storage::disk('public')->exists($doc->codigo_path)) {
                Storage::disk('public')->delete($doc->codigo_path);
            }
        }

        // Eliminar documentos del lote
        Documento::where('lote_id', $lote)->delete();

        return redirect()->route('documentos.index')->with('success', "Lote '{$lote}' eliminado correctamente.");
    }

}
