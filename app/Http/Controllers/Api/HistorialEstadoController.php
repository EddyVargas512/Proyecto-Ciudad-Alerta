<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialEstado;
use Illuminate\Http\Request;

class HistorialEstadoController extends Controller
{
    // Lista los cambios de estado registrados en las incidencias.
    public function index()
    {
        return response()->json(HistorialEstado::latest()->get(), 200);
    }

    // Valida el reporte, los estados y el usuario antes de guardar un cambio histórico.
    public function store(Request $request)
    {
        $data = $request->validate([
            'IdIncidencia' => 'required|exists:incidencias,IdIncidencia',
            'EstadoAnterior' => 'required|string|max:50',
            'EstadoNuevo' => 'required|string|max:50',
            'IdUsuario' => 'required|exists:usuarios,IdUsuario'
        ]);

        $historial = HistorialEstado::create($data);

        return response()->json($historial, 201);
    }

    // Busca un registro del historial por ID o responde 404 si no existe.
    public function show($id)
    {
        $historial = HistorialEstado::findOrFail($id);
        return response()->json($historial, 200);
    }

    // Permite corregir campos del historial enviados por la petición.
    public function update(Request $request, $id)
    {
        $historial = HistorialEstado::findOrFail($id);

        $data = $request->validate([
            'IdIncidencia' => 'sometimes|required|exists:incidencias,IdIncidencia',
            'EstadoAnterior' => 'sometimes|required|string|max:50',
            'EstadoNuevo' => 'sometimes|required|string|max:50',
            'IdUsuario' => 'sometimes|required|exists:usuarios,IdUsuario'
        ]);

        $historial->update($data);

        return response()->json($historial, 200);
    }

    // Elimina el registro histórico seleccionado.
    public function destroy($id)
    {
        $historial = HistorialEstado::findOrFail($id);
        $historial->delete();

        return response()->json(['message' => 'Historial de estado eliminado'], 200);
    }
}
