<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoController;

Route::get('/', [DocumentoController::class, 'index'])->name('documentos.index');
Route::post('/importar', [DocumentoController::class, 'importarExcel'])->name('documentos.importar');
Route::get('/exportar-excel', [DocumentoController::class, 'exportarExcel'])->name('documentos.exportar');
Route::post('/lote/eliminar', [DocumentoController::class, 'eliminarLote'])->name('documentos.eliminarLote');
Route::delete('/documentos/{id}', [DocumentoController::class, 'destroy'])->name('documentos.destroy');
Route::post('/documentos/editar-lote', [DocumentoController::class, 'editarLote'])->name('documentos.editarLote');
