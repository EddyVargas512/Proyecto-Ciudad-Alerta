<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use Illuminate\Http\Request;

class AsignacionController extends Controller
{
    // Lista las asignaciones existentes entre técnicos e incidencias.
    public function index()
    {
        return response()->json(Asignacion::latest()->get(), 200);
    }

    // Verifica que existan la incidencia y el técnico antes de crear la asignación.
    public function store(Request $request)
    {
        $data = $request->validate([
            'IdIncidencia' => 'required|exists:incidencias,IdIncidencia',
            'IdTecnico' => 'required|exists:tecnicos,IdTecnico',
            'FechaAsignacion' => 'nullable|date'
        ]);

        $asignacion = Asignacion::create($data);

        return response()->json($asignacion, 201);
    }

    // Recupera una asignación por ID o genera 404 si no se encuentra.
    public function show($id)
    {
        $asignacion = Asignacion::findOrFail($id);
        return response()->json($asignacion, 200);
    }

    // Cambia los datos de una asignación después de validar las referencias recibidas.
    public function update(Request $request, $id)
    {
        $asignacion = Asignacion::findOrFail($id);

        $data = $request->validate([
            'IdIncidencia' => 'sometimes|required|exists:incidencias,IdIncidencia',
            'IdTecnico' => 'sometimes|required|exists:tecnicos,IdTecnico',
            'FechaAsignacion' => 'sometimes|nullable|date'
        ]);

        $asignacion->update($data);

        return response()->json($asignacion, 200);
    }

    // Quita la asignación seleccionada y devuelve una confirmación.
    public function destroy($id)
    {
        $asignacion = Asignacion::findOrFail($id);
        $asignacion->delete();

        return response()->json(['message' => 'Asignacion eliminada'], 200);
    }
}
