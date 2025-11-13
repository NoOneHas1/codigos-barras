<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    // Campos permitidos para asignación masiva
    protected $fillable = [
        'tipo_doc',
        'numero_doc',
        'nombre',
        'codigo_path',
        'lote_id',
    ];
}
