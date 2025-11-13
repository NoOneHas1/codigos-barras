<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/', [DocumentoController::class, 'index'])->name('documentos.index');
Route::post('/importar', [DocumentoController::class, 'importarExcel'])->name('documentos.importar');
Route::get('/exportar-excel', [DocumentoController::class, 'exportarExcel'])->name('documentos.exportar');