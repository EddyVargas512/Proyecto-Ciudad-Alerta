<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApoyoIncidencia extends Model
{
    use HasFactory;

    // Este modelo representa el apoyo que un usuario da a una incidencia.
    protected $table = 'apoyos_incidencias';
    protected $primaryKey = 'IdApoyo';

    protected $fillable = [
        'IdIncidencia',
        'IdUsuario'
    ];
}
