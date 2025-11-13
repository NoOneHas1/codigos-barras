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
    // ============================
    // 📌 MOSTRAR DOCUMENTOS DEL ÚLTIMO LOTE
    // ============================
    public function index(Request $request)
    {
        $lote = $request->get('lote') ?? Documento::max('lote_id');
        $documentos = Documento::where('lote_id', $lote)->get();

        return view('documentos.index', compact('documentos', 'lote'));
    }



    // ============================
    // 📌 IMPORTAR EXCEL Y CREAR NUEVO LOTE
    // ============================
    public function importarExcel(Request $request)
    {
        Log::info('=== INICIO DE IMPORTACIÓN ===');

        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls'
        ]);

        try {

            // Guardar archivo temporal
            $file = $request->file('archivo');
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $tempPath = $file->storeAs('temp', $filename);

            $fullPath = Storage::path($tempPath);
            $fullPath = str_replace('\\', '/', $fullPath);

            if (!file_exists($fullPath)) {
                throw new \Exception("Archivo no encontrado: {$fullPath}");
            }

            // Crear nuevo lote incremental
            $loteId = (Documento::max('lote_id') ?? 0) + 1;

            // Leer Excel
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $generator = new BarcodeGeneratorPNG();
            $dataExport = [];
            $totalGuardados = 0;

            foreach ($rows as $index => $row) {
                if ($index == 1) continue; // Saltar encabezado

                $tipo = trim($row['A'] ?? '');
                $numero = trim($row['B'] ?? '');
                $nombre = trim($row['C'] ?? '');

                if (empty($numero)) continue;

                // Crear código de barras
                $barcodeData = $generator->getBarcode($numero, $generator::TYPE_CODE_128);
                $barcodeFile = 'codigos_barras/barcode_' . uniqid() . '.png';
                Storage::disk('public')->put($barcodeFile, $barcodeData);

                // Guardar en BD
                Documento::create([
                    'tipo_doc' => $tipo,
                    'numero_doc' => $numero,
                    'nombre' => $nombre,
                    'codigo_path' => $barcodeFile,
                    'lote_id' => $loteId,
                ]);

                $dataExport[] = [$tipo, $numero, $nombre, asset('storage/' . $barcodeFile)];
                $totalGuardados++;
            }

            // Crear Excel de salida simple
            $export = new Spreadsheet();
            $sheet = $export->getActiveSheet();
            $sheet->fromArray(['Tipo Documento', 'Número', 'Nombre', 'Ruta Código'], null, 'A1');
            $sheet->fromArray($dataExport, null, 'A2');

            $outputPath = storage_path('app/public/resultados_codigos.xlsx');
            $writer = IOFactory::createWriter($export, 'Xlsx');
            $writer->save($outputPath);

            // Limpiar archivo temporal
            Storage::delete($tempPath);

            return redirect()
                ->route('documentos.index', ['lote' => $loteId])
                ->with('success', "Archivo procesado correctamente. Se creó el lote: {$loteId}");

        } catch (\Exception $e) {
            Log::error("Error durante la importación: " . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', "Error al procesar el archivo: " . $e->getMessage());
        }
    }



    // ============================
    // 📌 EXPORTAR SOLO EL LOTE ACTUAL (CON AJUSTE PERFECTO DE IMAGEN)
    // ============================
    public function exportarExcel(Request $request)
    {
        $lote = $request->get('lote') ?? Documento::max('lote_id');
        $documentos = Documento::where('lote_id', $lote)->get();

        if ($documentos->isEmpty()) {
            return redirect()->back()->with('error', 'No hay documentos para exportar en este lote.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Lote {$lote}");

        // Encabezados
        $sheet->fromArray(['ID', 'Tipo Documento', 'Número', 'Nombre', 'Código GS1-128', 'Fecha'], null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        // Optimizar columnas
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(40); // donde irá imagen
        $sheet->getColumnDimension('F')->setWidth(20);

        $fila = 2;

        foreach ($documentos as $doc) {

            // Escribir datos
            $sheet->setCellValue('A' . $fila, $doc->id);
            $sheet->setCellValue('B' . $fila, $doc->tipo_doc);
            $sheet->setCellValue('C' . $fila, $doc->numero_doc);
            $sheet->setCellValue('D' . $fila, $doc->nombre);
            $sheet->setCellValue('F' . $fila, $doc->created_at->format('Y-m-d H:i'));

            // Ajustar altura de fila para carnet
            $sheet->getRowDimension($fila)->setRowHeight(50);

            $imagePath = storage_path('app/public/' . $doc->codigo_path);

            if (file_exists($imagePath)) {

                $drawing = new Drawing();
                $drawing->setPath($imagePath);

                // --- Ajuste perfecto para carnet ---
                $drawing->setWidth(160);     // más pequeño
                $drawing->setHeight(40);     // mantiene proporción
                $drawing->setOffsetX(5);     // centrado horizontal
                $drawing->setOffsetY(5);     // centrado vertical

                $drawing->setCoordinates('E' . $fila);
                $drawing->setWorksheet($sheet);
            }
            $fila++;
        }

        // Guardar archivo
        $fileName = 'documentos_lote_' . $lote . '_' . date('Ymd_His') . '.xlsx';
        $path = storage_path('app/public/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}