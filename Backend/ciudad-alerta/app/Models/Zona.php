<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    // Define los tipos de zona que pueden seleccionarse en un reporte.
    protected $table = 'zonas';
    protected $primaryKey = 'IdZona';

    protected $fillable = [
        'NombreZona'
    ];
}
