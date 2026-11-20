<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApoyoIncidencia;
use App\Models\Incidencia;
use App\Models\Usuario;
use App\Services\IncidenciaPrioridadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApoyoIncidenciaController extends Controller
{
    public function toggle(Request $request, Incidencia $incidencia, IncidenciaPrioridadService $prioridades): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ((int) $incidencia->IdUsuario === (int) $usuario->IdUsuario) {
            return response()->json(['message' => 'No puedes apoyar tu propio reporte.'], 422);
        }

        if (in_array(mb_strtolower($incidencia->Estado), ['resuelta', 'cerrada'], true)) {
            return response()->json(['message' => 'Este reporte ya fue resuelto y no admite nuevos apoyos.'], 422);
        }

        // El mismo botón agrega o retira el apoyo; el bloqueo evita duplicarlo en clics simultáneos.
        $apoyado = DB::transaction(function () use ($incidencia, $usuario): bool {
            // El bloqueo serializa clics simultáneos y evita dos apoyos del mismo usuario.
            Incidencia::query()->lockForUpdate()->findOrFail($incidencia->IdIncidencia);
            $apoyo = ApoyoIncidencia::query()
                ->where('IdIncidencia', $incidencia->IdIncidencia)
                ->where('IdUsuario', $usuario->IdUsuario)
                ->first();

            if ($apoyo) {
                ApoyoIncidencia::query()
                    ->where('IdIncidencia', $incidencia->IdIncidencia)
                    ->where('IdUsuario', $usuario->IdUsuario)
                    ->delete();
                $apoyado = false;
            } else {
                ApoyoIncidencia::create([
                    'IdIncidencia' => $incidencia->IdIncidencia,
                    'IdUsuario' => $usuario->IdUsuario,
                ]);
                $apoyado = true;
            }

            $cantidad = ApoyoIncidencia::query()->where('IdIncidencia', $incidencia->IdIncidencia)->count();
            Incidencia::query()->whereKey($incidencia->IdIncidencia)->update(['CantidadApoyos' => $cantidad]);

            return $apoyado;
        });

        $incidencia->refresh();
        $prioridades->actualizarCercanas($incidencia);
        $resumen = $prioridades->resumenes(new \Illuminate\Database\Eloquent\Collection([$incidencia]))[$incidencia->IdIncidencia];

        return response()->json([
            'ApoyadoPorMi' => $apoyado,
            'CantidadApoyos' => (int) $incidencia->CantidadApoyos,
            ...$resumen,
        ]);
    }
}
