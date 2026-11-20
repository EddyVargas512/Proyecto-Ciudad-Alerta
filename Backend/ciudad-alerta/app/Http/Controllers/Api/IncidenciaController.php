<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApoyoIncidencia;
use App\Models\Asignacion;
use App\Models\Evidencia;
use App\Models\HistorialEstado;
use App\Models\Incidencia;
use App\Models\Usuario;
use App\Services\IncidenciaPrioridadService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class IncidenciaController extends Controller
{
    public function index(Request $request, IncidenciaPrioridadService $prioridades): JsonResponse
    {
        $incidencias = Incidencia::query()
            ->where('Estado', '!=', 'Resuelta')
            ->with(['evidencias', 'asignaciones.tecnico'])
            ->withCount('apoyos')
            ->latest()
            ->get();

        return response()->json($this->presentar($incidencias, $request->user(), $prioridades));
    }

    public function adminIndex(Request $request, IncidenciaPrioridadService $prioridades): JsonResponse
    {
        $incidencias = Incidencia::query()
            ->with(['usuario', 'categoria', 'zona', 'evidencias', 'asignaciones.tecnico'])
            ->withCount('apoyos')
            ->latest()
            ->get();

        return response()->json($this->presentar($incidencias, $request->user(), $prioridades));
    }

    public function show(Request $request, Incidencia $incidencia, IncidenciaPrioridadService $prioridades): JsonResponse
    {
        $incidencia->load(['evidencias', 'asignaciones.tecnico'])->loadCount('apoyos');

        return response()->json($this->presentar(new Collection([$incidencia]), $request->user(), $prioridades)[0]);
    }

    public function store(Request $request, IncidenciaPrioridadService $prioridades): JsonResponse
    {
        // Estado, autor y apoyos iniciales los define el servidor; no se aceptan del formulario.
        $data = $request->validate([
            'Titulo' => ['required', 'string', 'max:150'],
            'Descripcion' => ['required', 'string', 'max:10000'],
            'UbicacionTexto' => ['required', 'string', 'max:255'],
            'Latitud' => ['required', 'numeric', 'between:-90,90'],
            'Longitud' => ['required', 'numeric', 'between:-180,180'],
            'IdCategoria' => ['required', 'integer', 'exists:categorias,IdCategoria'],
            'IdZona' => ['required', 'integer', 'exists:zonas,IdZona'],
            'Evidencias' => ['required', 'array', 'min:1', 'max:5'],
            'Evidencias.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $archivos = $request->file('Evidencias', []);
        unset($data['Evidencias']);
        $archivosGuardados = [];

        try {
            // La incidencia y sus filas de fotos se guardan juntas para no dejar datos a medias.
            $incidencia = DB::transaction(function () use ($data, $archivos, &$archivosGuardados, $request, $prioridades): Incidencia {
                $incidencia = Incidencia::create([
                    ...$data,
                    'Estado' => 'Pendiente',
                    'CantidadApoyos' => 0,
                    'IdPrioridad' => $prioridades->obtenerIdPrioridad('Baja'),
                    'IdUsuario' => $request->user()->IdUsuario,
                ]);

                foreach ($archivos as $archivo) {
                    $ruta = $archivo->store('incidencias/'.$incidencia->IdIncidencia, 'public');
                    if (! $ruta) {
                        throw new RuntimeException('No se pudo guardar una de las fotografías.');
                    }

                    $archivosGuardados[] = $ruta;
                    Evidencia::create([
                        'IdIncidencia' => $incidencia->IdIncidencia,
                        'RutaArchivo' => $ruta,
                        'TipoArchivo' => $archivo->extension(),
                    ]);
                }

                return $incidencia->load(['evidencias', 'asignaciones.tecnico'])->loadCount('apoyos');
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($archivosGuardados);
            throw $exception;
        }

        $prioridades->actualizarCercanas($incidencia);
        $incidencia->refresh()->load(['evidencias', 'asignaciones.tecnico'])->loadCount('apoyos');

        return response()->json($this->presentar(new Collection([$incidencia]), $request->user(), $prioridades)[0], 201);
    }

    public function updateStatus(Request $request, Incidencia $incidencia): JsonResponse
    {
        $data = $request->validate([
            'Estado' => ['required', 'string', 'in:Pendiente,En proceso,Resuelta'],
        ]);

        // Guardamos el estado anterior y el nuevo en una sola transacción para mantener historial.
        DB::transaction(function () use ($incidencia, $data, $request): void {
            $incidencia = Incidencia::query()->lockForUpdate()->findOrFail($incidencia->IdIncidencia);
            $estadoAnterior = $incidencia->Estado;

            if ($estadoAnterior === $data['Estado']) {
                return;
            }

            $incidencia->update(['Estado' => $data['Estado']]);
            HistorialEstado::create([
                'IdIncidencia' => $incidencia->IdIncidencia,
                'EstadoAnterior' => $estadoAnterior,
                'EstadoNuevo' => $data['Estado'],
                'IdUsuario' => $request->user()->IdUsuario,
            ]);
        });

        return response()->json(['message' => 'El estado se actualizó correctamente.']);
    }

    public function assignTechnician(Request $request, Incidencia $incidencia): JsonResponse
    {
        $data = $request->validate([
            'IdTecnico' => ['required', 'integer', 'exists:tecnicos,IdTecnico'],
        ]);

        // La asignación queda ligada al reporte; si estaba pendiente, pasa a "En proceso".
        DB::transaction(function () use ($incidencia, $data, $request): void {
            $locked = Incidencia::query()->lockForUpdate()->findOrFail($incidencia->IdIncidencia);
            $asignacion = $locked->asignaciones()->latest('FechaAsignacion')->first();

            if ($asignacion) {
                $asignacion->update(['IdTecnico' => $data['IdTecnico'], 'FechaAsignacion' => now()]);
            } else {
                Asignacion::create([
                    'IdIncidencia' => $locked->IdIncidencia,
                    'IdTecnico' => $data['IdTecnico'],
                    'FechaAsignacion' => now(),
                ]);
            }

            if ($locked->Estado === 'Pendiente') {
                $locked->update(['Estado' => 'En proceso']);
                HistorialEstado::create([
                    'IdIncidencia' => $locked->IdIncidencia,
                    'EstadoAnterior' => 'Pendiente',
                    'EstadoNuevo' => 'En proceso',
                    'IdUsuario' => $request->user()->IdUsuario,
                ]);
            }
        });

        return response()->json(['message' => 'El técnico se asignó correctamente.']);
    }

    public function destroy(Incidencia $incidencia, IncidenciaPrioridadService $prioridades): JsonResponse
    {
        $incidencia->load('evidencias');
        $rutasArchivos = $incidencia->evidencias->pluck('RutaArchivo')->all();

        // Primero quitamos las filas dependientes para no dejar apoyos, fotos o historial huérfanos.
        DB::transaction(function () use ($incidencia): void {
            ApoyoIncidencia::query()->where('IdIncidencia', $incidencia->IdIncidencia)->delete();
            Asignacion::query()->where('IdIncidencia', $incidencia->IdIncidencia)->delete();
            Evidencia::query()->where('IdIncidencia', $incidencia->IdIncidencia)->delete();
            HistorialEstado::query()->where('IdIncidencia', $incidencia->IdIncidencia)->delete();
            DB::table('comentarios')->where('IdIncidencia', $incidencia->IdIncidencia)->delete();
            $incidencia->delete();
        });

        Storage::disk('public')->delete($rutasArchivos);
        $prioridades->actualizarCercanas($incidencia);

        return response()->json(['message' => 'La incidencia y sus evidencias se eliminaron correctamente.']);
    }

    private function presentar(Collection $incidencias, ?Usuario $usuario, IncidenciaPrioridadService $prioridades): array
    {
        // Agrega datos calculados para la interfaz sin guardarlos como campos nuevos en la tabla.
        $resumenes = $prioridades->resumenes($incidencias);
        $apoyadasPorUsuario = $usuario
            ? ApoyoIncidencia::query()->where('IdUsuario', $usuario->IdUsuario)->whereIn('IdIncidencia', $incidencias->pluck('IdIncidencia'))->pluck('IdIncidencia')->all()
            : [];

        return $incidencias->map(function (Incidencia $incidencia) use ($resumenes, $apoyadasPorUsuario): array {
            $asignacion = $incidencia->relationLoaded('asignaciones')
                ? $incidencia->asignaciones->sortByDesc('FechaAsignacion')->first()
                : null;

            return [
                ...$incidencia->toArray(),
                ...($resumenes[$incidencia->IdIncidencia] ?? []),
                'CantidadApoyos' => (int) ($incidencia->apoyos_count ?? $incidencia->CantidadApoyos ?? 0),
                'ApoyadoPorMi' => in_array($incidencia->IdIncidencia, $apoyadasPorUsuario, false),
                'AsignacionActual' => $asignacion ? [
                    'IdAsignacion' => $asignacion->IdAsignacion,
                    'IdTecnico' => $asignacion->IdTecnico,
                    'FechaAsignacion' => $asignacion->FechaAsignacion,
                    'Tecnico' => $asignacion->tecnico?->only(['IdTecnico', 'Nombre', 'Email']),
                ] : null,
            ];
        })->values()->all();
    }
}
