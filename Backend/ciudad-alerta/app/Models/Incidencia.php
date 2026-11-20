<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    // Indica a Eloquent qué tabla y clave primaria usa este modelo en la base de datos.
    protected $table = 'incidencias';
    protected $primaryKey = 'IdIncidencia';

    // Solo estos campos pueden asignarse directamente al crear o actualizar un reporte.
    protected $fillable = [
        'Titulo',
        'Descripcion',
        'UbicacionTexto',
        'Latitud',
        'Longitud',
        'Estado',
        'CantidadApoyos',
        'IdUsuario',
        'IdCategoria',
        'IdPrioridad',
        'IdZona'
    ];

    public function usuario(): BelongsTo
    {
        // Cada incidencia pertenece al usuario que la reportó.
        return $this->belongsTo(Usuario::class, 'IdUsuario', 'IdUsuario');
    }

    public function categoria(): BelongsTo
    {
        // Relaciona la incidencia con su categoría.
        return $this->belongsTo(Categoria::class, 'IdCategoria', 'IdCategoria');
    }

    public function zona(): BelongsTo
    {
        // Relaciona la incidencia con la zona urbana o rural seleccionada.
        return $this->belongsTo(Zona::class, 'IdZona', 'IdZona');
    }

    public function prioridad(): BelongsTo
    {
        // Relaciona la incidencia con el nivel de prioridad guardado.
        return $this->belongsTo(Prioridad::class, 'IdPrioridad', 'IdPrioridad');
    }

    public function apoyos(): HasMany
    {
        // Una incidencia puede recibir apoyos de varios usuarios.
        return $this->hasMany(ApoyoIncidencia::class, 'IdIncidencia', 'IdIncidencia');
    }

    public function evidencias(): HasMany
    {
        // Un reporte puede tener varias fotografías o archivos de evidencia.
        return $this->hasMany(Evidencia::class, 'IdIncidencia', 'IdIncidencia');
    }

    public function asignaciones(): HasMany
    {
        // Guarda el historial de técnicos asignados a esta incidencia.
        return $this->hasMany(Asignacion::class, 'IdIncidencia', 'IdIncidencia');
    }

    public function historialEstados(): HasMany
    {
        // Permite consultar los cambios de estado que ha tenido el reporte.
        return $this->hasMany(HistorialEstado::class, 'IdIncidencia', 'IdIncidencia');
    }
}
