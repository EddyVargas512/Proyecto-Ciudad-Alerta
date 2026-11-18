<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dependencia extends Model
{
    use HasFactory;

    // Guarda la institución o dependencia responsable de atender reportes.
    protected $table = 'dependencias';
    protected $primaryKey = 'IdDependencia';

    protected $fillable = [
        'NombreDependencia'
    ];
}
