<?php

use App\Http\Controllers\DocumentoController;

Route::get('/documentos', [DocumentoController::class, 'index'])->name('documentos.index');
Route::post('/documentos/importar', [DocumentoController::class, 'importarExcel'])->name('documentos.importar');
Route::get('/documentos/exportar', [DocumentoController::class, 'exportarExcel'])->name('documentos.exportar');
Route::post('/documentos/limpiar', [DocumentoController::class, 'limpiar'])->name('documentos.limpiar');