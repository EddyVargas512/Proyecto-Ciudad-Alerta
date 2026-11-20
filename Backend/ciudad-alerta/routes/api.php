<?php

use App\Http\Controllers\Api\ApoyoIncidenciaController;
use App\Http\Controllers\Api\AsignacionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ComentarioController;
use App\Http\Controllers\Api\DependenciaController;
use App\Http\Controllers\Api\EvidenciaController;
use App\Http\Controllers\Api\HistorialEstadoController;
use App\Http\Controllers\Api\IncidenciaController;
use App\Http\Controllers\Api\PrioridadController;
use App\Http\Controllers\Api\TecnicoController;
use App\Http\Controllers\Api\TipoUsuarioController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\ZonaController;
use Illuminate\Support\Facades\Route;

// POST recibe los datos de registro; el límite reduce intentos automáticos repetidos.
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
// POST verifica credenciales y también limita intentos de inicio de sesión.
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Todas estas rutas requieren un token Sanctum válido enviado como Bearer.
Route::middleware('auth:sanctum')->group(function (): void {
    // GET devuelve el usuario autenticado; PUT actualiza su perfil; POST cierra su sesión actual.
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // GET muestra reportes visibles para usuarios; el detalle GET consulta uno específico.
    Route::get('/incidencias', [IncidenciaController::class, 'index']);
    Route::get('/incidencias/{incidencia}', [IncidenciaController::class, 'show']);
    // POST crea un reporte; solo los roles indicados pueden hacerlo.
    Route::post('/incidencias', [IncidenciaController::class, 'store'])->middleware('role:Ciudadano,Administrador');
    // POST alterna el apoyo del ciudadano: si ya apoyó, puede retirar su apoyo.
    Route::post('/incidencias/{incidencia}/apoyo', [ApoyoIncidenciaController::class, 'toggle'])->middleware('role:Ciudadano');

    // Estos GET entregan opciones de catálogo para completar el formulario de incidencias.
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/zonas', [ZonaController::class, 'index']);
    Route::get('/prioridades', [PrioridadController::class, 'index']);

    // Además del token, estas acciones requieren que el usuario tenga rol Administrador.
    Route::prefix('admin')->middleware('role:Administrador')->group(function (): void {
        // Estas rutas permiten listar, borrar, cambiar estado y asignar técnico a reportes.
        Route::get('/incidencias', [IncidenciaController::class, 'adminIndex']);
        Route::delete('/incidencias/{incidencia}', [IncidenciaController::class, 'destroy']);
        Route::patch('/incidencias/{incidencia}/estado', [IncidenciaController::class, 'updateStatus']);
        Route::put('/incidencias/{incidencia}/asignacion', [IncidenciaController::class, 'assignTechnician']);

        // apiResource crea las rutas estándar de listar, ver, crear, editar y borrar cada recurso.
        // Todas quedan protegidas por el rol Administrador aplicado al grupo completo.
        Route::apiResource('usuarios', UsuarioController::class);
        Route::apiResource('tipos-usuarios', TipoUsuarioController::class);
        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('zonas', ZonaController::class);
        Route::apiResource('prioridades', PrioridadController::class);
        Route::apiResource('dependencias', DependenciaController::class);
        Route::apiResource('tecnicos', TecnicoController::class);
        Route::apiResource('asignaciones', AsignacionController::class);
        Route::apiResource('evidencias', EvidenciaController::class);
        Route::apiResource('comentarios', ComentarioController::class);
        Route::apiResource('historial-estados', HistorialEstadoController::class);
    });
});
