<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dependencia;
use Illuminate\Http\Request;

class DependenciaController extends Controller
{
    // Devuelve todas las dependencias registradas.
    public function index()
    {
        return response()->json(Dependencia::latest()->get(), 200);
    }

    // Valida el nombre recibido y crea la dependencia.
    public function store(Request $request)
    {
        $data = $request->validate([
            'NombreDependencia' => 'required|string|max:100'
        ]);

        $dependencia = Dependencia::create($data);

        return response()->json($dependencia, 201);
    }

    // Busca una dependencia por identificador o responde 404.
    public function show($id)
    {
        $dependencia = Dependencia::findOrFail($id);
        return response()->json($dependencia, 200);
    }

    // Actualiza solo los campos incluidos en la petición y validados.
    public function update(Request $request, $id)
    {
        $dependencia = Dependencia::findOrFail($id);

        $data = $request->validate([
            'NombreDependencia' => 'sometimes|required|string|max:100'
        ]);

        $dependencia->update($data);

        return response()->json($dependencia, 200);
    }

    // Elimina la dependencia encontrada y confirma el resultado.
    public function destroy($id)
    {
        $dependencia = Dependencia::findOrFail($id);
        $dependencia->delete();

        return response()->json(['message' => 'Dependencia eliminada'], 200);
    }
}
