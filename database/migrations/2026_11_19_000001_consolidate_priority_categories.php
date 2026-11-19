<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categorias') || ! Schema::hasTable('incidencias')) {
            return;
        }

        // Junta las categorías anteriores y conserva las referencias de los reportes.
        $groups = [
            '1 - Alta prioridad' => ['1 - Máxima prioridad', '2 - Alta prioridad'],
            '2 - Normal' => ['3 - Prioridad media'],
            '3 - Baja prioridad' => ['4 - Baja prioridad', '5 - Mínima prioridad'],
        ];

        foreach ($groups as $canonicalName => $oldNames) {
            $matchingIds = DB::table('categorias')
                ->whereIn('NombreCategoria', array_merge([$canonicalName], $oldNames))
                ->orderBy('IdCategoria')
                ->pluck('IdCategoria');

            if ($matchingIds->isEmpty()) {
                $targetId = DB::table('categorias')->insertGetId([
                    'NombreCategoria' => $canonicalName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'IdCategoria');
                continue;
            }

            $targetId = $matchingIds->first();
            DB::table('categorias')->where('IdCategoria', $targetId)->update([
                'NombreCategoria' => $canonicalName,
                'updated_at' => now(),
            ]);

            DB::table('incidencias')->whereIn('IdCategoria', $matchingIds)->update([
                'IdCategoria' => $targetId,
            ]);

            DB::table('categorias')->whereIn('IdCategoria', $matchingIds->slice(1))->delete();
        }

        // Renombra Media a Normal para que los niveles calculados usen las mismas tres opciones.
        if (Schema::hasTable('prioridades')) {
            $normalId = DB::table('prioridades')->where('NivelPrioridad', 'Normal')->value('IdPrioridad');
            $mediaId = DB::table('prioridades')->where('NivelPrioridad', 'Media')->value('IdPrioridad');

            if ($mediaId && $normalId && $mediaId !== $normalId) {
                DB::table('incidencias')->where('IdPrioridad', $mediaId)->update(['IdPrioridad' => $normalId]);
                DB::table('prioridades')->where('IdPrioridad', $mediaId)->delete();
            } elseif ($mediaId && ! $normalId) {
                DB::table('prioridades')->where('IdPrioridad', $mediaId)->update(['NivelPrioridad' => 'Normal']);
            }
        }
    }

    public function down(): void
    {
        // La unión de categorías antiguas no se revierte para evitar romper reportes existentes.
    }
};
