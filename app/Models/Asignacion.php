<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asignacion extends Model
{
    use HasFactory;

    protected $table = 'asignaciones';
    protected $primaryKey = 'IdAsignacion';

    protected $fillable = [
        'IdIncidencia',
        'IdTecnico',
        'FechaAsignacion'
    ];

    public function tecnico(): BelongsTo
    {
        // Permite obtener los datos del técnico asociado a esta asignación.
        return $this->belongsTo(Tecnico::class, 'IdTecnico', 'IdTecnico');
    }
}
