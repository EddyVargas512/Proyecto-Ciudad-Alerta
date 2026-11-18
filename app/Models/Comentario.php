<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    use HasFactory;

    // Representa los comentarios que los usuarios publican en una incidencia.
    protected $table = 'comentarios';
    protected $primaryKey = 'IdComentario';

    protected $fillable = [
        'IdIncidencia',
        'IdUsuario',
        'TextoComentario'
    ];
}
