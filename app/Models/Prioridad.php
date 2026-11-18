<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prioridad extends Model
{
    use HasFactory;

    // Define la tabla con los niveles de prioridad disponibles.
    protected $table = 'prioridades';
    protected $primaryKey = 'IdPrioridad';

    protected $fillable = [
        'NivelPrioridad'
    ];
}
