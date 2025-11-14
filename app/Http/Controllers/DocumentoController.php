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
        $lote = $request->get('lote');  // ← es un número, no un objeto

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
    // 📌 IMPORTAR EXCEL Y CREAR NUEVO LOTE
    // ============================
    public function importarExcel(Request $request)
    {
        Log::info('=== INICIO DE IMPORTACIÓN ===');

        // Validación del front
        $request->validate([
            'archivo' => 'required|file'
        ]);

        try {

            // Archivo subido
            $file = $request->file('archivo');

            // Validar EXTENSIÓN permitida
            $ext = strtolower($file->getClientOriginalExtension());
            $valid = ['xlsx', 'xls', 'csv', 'xlsm'];

            if (!in_array($ext, $valid)) {
                throw new \Exception("Formato NO permitido. Solo Excel (xlsx, xls, csv, xlsm).");
            }

            // Guardar temporal
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $tempPath = $file->storeAs('temp', $filename);

            $fullPath = str_replace('\\', '/', Storage::path($tempPath));

            if (!file_exists($fullPath)) {
                throw new \Exception("Archivo no encontrado: {$fullPath}");
            }

            // Cargar Excel correctamente
            try {
                $spreadsheet = IOFactory::load($fullPath);
            } catch (\Throwable $t) {
                throw new \Exception("No se pudo leer el archivo. Asegúrate de que sea un archivo Excel válido.");
            }

            // Ahora que el archivo es válido → crear lote
            $loteId = (Documento::max('lote_id') ?? 0) + 1;

            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $generator = new BarcodeGeneratorPNG();
            $dataExport = [];
            $totalGuardados = 0;

            foreach ($rows as $index => $row) {
                if ($index == 1) continue; // encabezado

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

            Storage::delete($tempPath);

            return redirect()->route('documentos.index', [
                'lote' => $loteId
            ])->with('success', "Importación completada: {$totalGuardados} documentos cargados.");

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
        // Validar que venga un lote
        $request->validate([
            'lote_id' => 'required|numeric|exists:documentos,lote_id'
        ]);

        $lote = $request->lote_id;

        // Obtener documentos del lote seleccionado
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

            // Ajuste perfecto tipo carnet
            $sheet->getRowDimension($fila)->setRowHeight(50);

            $imagePath = storage_path('app/public/' . $doc->codigo_path);

            if (file_exists($imagePath)) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setPath($imagePath);

                // Tamaño más pequeño para carnets
                $drawing->setWidth(110);
                $drawing->setHeight(28);
                $drawing->setOffsetX(3);
                $drawing->setOffsetY(18);

                $drawing->setCoordinates("E{$fila}");
                $drawing->setWorksheet($sheet);
            }

            $fila++;
        }

        $fileName = "documentos_lote_{$lote}_" . date('Ymd_His') . ".xlsx";
        $filePath = storage_path("app/public/{$fileName}");

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}