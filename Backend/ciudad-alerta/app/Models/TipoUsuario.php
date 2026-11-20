<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoUsuario extends Model
{
    use HasFactory;

    // Contiene los roles asignables a una cuenta, como Ciudadano y Administrador.
    protected $table = 'tipo_usuarios';
    protected $primaryKey = 'IdTipoUsuario';

    protected $fillable = [
        'NombreTipo'
    ];
}
