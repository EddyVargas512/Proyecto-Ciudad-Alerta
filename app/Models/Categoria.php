<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    // Configura la tabla de categorías y el nombre que se acepta al guardar.
    protected $table = 'categorias';
    protected $primaryKey = 'IdCategoria';

    protected $fillable = [
        'NombreCategoria'
    ];
}
