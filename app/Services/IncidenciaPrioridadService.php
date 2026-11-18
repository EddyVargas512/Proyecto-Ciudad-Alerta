<?php

namespace App\Services;

use App\Models\ApoyoIncidencia;
use App\Models\Categoria;
use App\Models\Incidencia;
use App\Models\Prioridad;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class IncidenciaPrioridadService
{
    // Dos incidencias cuentan como cercanas si sus coordenadas están a 100 m o menos.
    private const RADIO_CERCANIA_METROS = 100.0;

    public function resumenes(Collection $incidencias): array
    {
        $todas = Incidencia::query()->get(['IdIncidencia', 'Latitud', 'Longitud', 'IdCategoria']);
        $categorias = Categoria::query()->pluck('NombreCategoria', 'IdCategoria');
        $apoyos = ApoyoIncidencia::query()
            ->selectRaw('IdIncidencia, COUNT(*) AS total')
            ->groupBy('IdIncidencia')
            ->pluck('total', 'IdIncidencia');

        $resumenes = [];

        foreach ($incidencias as $incidencia) {
            $cercanas = $todas->filter(fn (Incidencia $otra) => $this->distanciaMetros($incidencia, $otra) <= self::RADIO_CERCANIA_METROS);
            $idsCercanas = $cercanas->pluck('IdIncidencia');
            $apoyosCercanos = $idsCercanas->sum(fn ($id) => (int) ($apoyos[$id] ?? 0));
            $cantidadCercanas = $cercanas->count();
            $nombreCategoria = (string) ($categorias[$incidencia->IdCategoria] ?? '');
            preg_match('/^([1-5])\s*-/u', $nombreCategoria, $coincidencias);
            $rangoCategoria = isset($coincidencias[1]) ? (int) $coincidencias[1] : null;
            // La categoría 1 empieza con más puntos; cada reporte cercano y apoyo suma más.
            $puntajeCategoria = match ($rangoCategoria) {
                1, 2 => 12,
                3 => 9,
                4 => 6,
                5 => 3,
                default => 0,
            };
            // No se cuenta la misma incidencia como "reporte adicional" dentro de su grupo.
            $puntaje = $puntajeCategoria + (max(0, $cantidadCercanas - 1) * 3) + $apoyosCercanos;

            $resumenes[$incidencia->IdIncidencia] = [
                'PuntajePrioridad' => $puntaje,
                'ReportesCercanos' => $cantidadCercanas,
                'ApoyosCercanos' => $apoyosCercanos,
                'PrioridadCalculada' => $this->nivelPorPuntaje($puntaje),
            ];
        }

        return $resumenes;
    }

    public function actualizarCercanas(Incidencia $incidencia): void
    {
        // Al crear o borrar un reporte, recalcula la prioridad de los demás que están cerca.
        $todas = Incidencia::query()->get(['IdIncidencia', 'Latitud', 'Longitud', 'IdPrioridad', 'IdCategoria']);
        $resumenes = $this->resumenes($todas);

        foreach ($todas as $otra) {
            if ($this->distanciaMetros($incidencia, $otra) > self::RADIO_CERCANIA_METROS) {
                continue;
            }

            $nivel = $resumenes[$otra->IdIncidencia]['PrioridadCalculada'] ?? 'Baja';
            $priorityId = $this->obtenerIdPrioridad($nivel);

            if ((int) $otra->IdPrioridad !== $priorityId) {
                Incidencia::query()->whereKey($otra->IdIncidencia)->update(['IdPrioridad' => $priorityId]);
            }
        }
    }

    public function obtenerIdPrioridad(string $nivel): int
    {
        $prioridad = Prioridad::query()->get()->first(
            fn (Prioridad $item) => Str::lower(Str::ascii($item->NivelPrioridad)) === Str::lower(Str::ascii($nivel)),
        );

        if (! $prioridad) {
            $prioridad = Prioridad::create(['NivelPrioridad' => $nivel]);
        }

        return (int) $prioridad->IdPrioridad;
    }

    private function nivelPorPuntaje(int $puntaje): string
    {
        // Estos límites convierten el puntaje total en los tres niveles usados por la app.
        if ($puntaje >= 12) {
            return 'Alta';
        }

        if ($puntaje >= 9) {
            return 'Media';
        }

        return 'Baja';
    }

    private function distanciaMetros(Incidencia $origen, Incidencia $destino): float
    {
        // Fórmula Haversine: calcula distancia sobre la Tierra usando latitud y longitud.
        $lat1 = deg2rad((float) $origen->Latitud);
        $lat2 = deg2rad((float) $destino->Latitud);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad((float) $destino->Longitud - (float) $origen->Longitud);
        $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;

        return 6_371_000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
