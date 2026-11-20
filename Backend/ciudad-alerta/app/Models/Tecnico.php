<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    use HasFactory;

    // Define la tabla, el identificador y los datos que se pueden guardar en un técnico.
    protected $table = 'tecnicos';
    protected $primaryKey = 'IdTecnico';

    protected $fillable = [
        'Nombre',
        'Email',
        'IdDependencia'
    ];
}
