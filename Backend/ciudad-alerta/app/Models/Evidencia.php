<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Evidencia extends Model
{
    use HasFactory;

    protected $table = 'evidencias';
    protected $primaryKey = 'IdEvidencia';

    protected $fillable = [
        'IdIncidencia',
        'RutaArchivo',
        'TipoArchivo'
    ];

    // Agrega la URL pública del archivo al JSON que devuelve la API.
    protected $appends = ['UrlArchivo'];

    public function getUrlArchivoAttribute(): string
    {
        // Convierte la ruta guardada en almacenamiento en un enlace accesible.
        return Storage::disk('public')->url($this->RutaArchivo);
    }
}
