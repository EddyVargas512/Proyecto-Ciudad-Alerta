<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prioridad;
use Illuminate\Http\Request;

class PrioridadController extends Controller
{
    // Lista los niveles de prioridad disponibles.
    public function index()
    {
        return response()->json(Prioridad::latest()->get(), 200);
    }

    // Valida el nombre del nivel y lo guarda en la base de datos.
    public function store(Request $request)
    {
        $data = $request->validate([
            'NivelPrioridad' => 'required|string|max:50'
        ]);

        $prioridad = Prioridad::create($data);

        return response()->json($prioridad, 201);
    }

    // Obtiene un nivel por su identificador o devuelve 404 si no existe.
    public function show($id)
    {
        $prioridad = Prioridad::findOrFail($id);
        return response()->json($prioridad, 200);
    }

    // Actualiza únicamente los datos recibidos y validados.
    public function update(Request $request, $id)
    {
        $prioridad = Prioridad::findOrFail($id);

        $data = $request->validate([
            'NivelPrioridad' => 'sometimes|required|string|max:50'
        ]);

        $prioridad->update($data);

        return response()->json($prioridad, 200);
    }

    // Elimina el nivel encontrado y devuelve un mensaje de confirmación.
    public function destroy($id)
    {
        $prioridad = Prioridad::findOrFail($id);
        $prioridad->delete();

        return response()->json(['message' => 'Prioridad eliminada'], 200);
    }
}
