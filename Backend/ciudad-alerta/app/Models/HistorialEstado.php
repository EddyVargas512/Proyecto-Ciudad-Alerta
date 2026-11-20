<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialEstado extends Model
{
    use HasFactory;

    // Representa cada cambio de estado realizado sobre una incidencia.
    protected $table = 'historial_estados';
    protected $primaryKey = 'IdHistorial';

    protected $fillable = [
        'IdIncidencia',
        'EstadoAnterior',
        'EstadoNuevo',
        'IdUsuario'
    ];
}
