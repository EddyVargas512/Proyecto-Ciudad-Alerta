<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Dependencia;
use App\Models\Prioridad;
use App\Models\TipoUsuario;
use App\Models\Zona;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Ciudadano', 'Administrador'] as $role) {
            TipoUsuario::firstOrCreate(['NombreTipo' => $role]);
        }

        foreach (['Baja', 'Normal', 'Alta'] as $priority) {
            Prioridad::firstOrCreate(['NivelPrioridad' => $priority]);
        }

        foreach ([
            '1 - Alta prioridad',
            '2 - Normal',
            '3 - Baja prioridad',
        ] as $category) {
            Categoria::firstOrCreate(['NombreCategoria' => $category]);
        }

        foreach (['Rural', 'Urbano'] as $zone) {
            Zona::firstOrCreate(['NombreZona' => $zone]);
        }

        Dependencia::firstOrCreate(['NombreDependencia' => 'Servicios generales']);
    }
}
