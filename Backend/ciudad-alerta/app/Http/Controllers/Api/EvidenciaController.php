<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evidencia;
use Illuminate\Http\Request;

class EvidenciaController extends Controller
{
    // Lista las evidencias asociadas a los reportes.
    public function index()
    {
        return response()->json(Evidencia::latest()->get(), 200);
    }

    // Comprueba que el reporte exista y que lleguen la ruta y el tipo de archivo.
    public function store(Request $request)
    {
        $data = $request->validate([
            'IdIncidencia' => 'required|exists:incidencias,IdIncidencia',
            'RutaArchivo' => 'required|string|max:255',
            'TipoArchivo' => 'required|string|max:50'
        ]);

        $evidencia = Evidencia::create($data);

        return response()->json($evidencia, 201);
    }

    // Busca una evidencia por ID; si no existe, la API responde 404.
    public function show($id)
    {
        $evidencia = Evidencia::findOrFail($id);
        return response()->json($evidencia, 200);
    }

    // Permite cambiar de forma parcial los datos de una evidencia.
    public function update(Request $request, $id)
    {
        $evidencia = Evidencia::findOrFail($id);

        $data = $request->validate([
            'IdIncidencia' => 'sometimes|required|exists:incidencias,IdIncidencia',
            'RutaArchivo' => 'sometimes|required|string|max:255',
            'TipoArchivo' => 'sometimes|required|string|max:50'
        ]);

        $evidencia->update($data);

        return response()->json($evidencia, 200);
    }

    // Elimina el registro de evidencia seleccionado.
    public function destroy($id)
    {
        $evidencia = Evidencia::findOrFail($id);
        $evidencia->delete();

        return response()->json(['message' => 'Evidencia eliminada'], 200);
    }
}
