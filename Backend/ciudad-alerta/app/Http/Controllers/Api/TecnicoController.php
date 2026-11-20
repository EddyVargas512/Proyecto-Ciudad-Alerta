<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tecnico;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    // Devuelve el equipo técnico para que administración pueda consultarlo.
    public function index()
    {
        return response()->json(Tecnico::latest()->get(), 200);
    }

    // Revisa nombre, correo único y dependencia válida antes de crear el técnico.
    public function store(Request $request)
    {
        $data = $request->validate([
            'Nombre' => 'required|string|max:150',
            'Email' => 'required|email|unique:tecnicos,Email|max:150',
            'IdDependencia' => 'required|exists:dependencias,IdDependencia'
        ]);

        $tecnico = Tecnico::create($data);

        return response()->json($tecnico, 201);
    }

    // Busca el técnico solicitado o responde con 404 si no está registrado.
    public function show($id)
    {
        $tecnico = Tecnico::findOrFail($id);
        return response()->json($tecnico, 200);
    }

    // Valida y modifica solo los datos enviados; permite conservar el correo actual.
    public function update(Request $request, $id)
    {
        $tecnico = Tecnico::findOrFail($id);

        $data = $request->validate([
            'Nombre' => 'sometimes|required|string|max:150',
            'Email' => 'sometimes|required|email|max:150|unique:tecnicos,Email,' . $id . ',IdTecnico',
            'IdDependencia' => 'sometimes|required|exists:dependencias,IdDependencia'
        ]);

        $tecnico->update($data);

        return response()->json($tecnico, 200);
    }

    // Elimina el técnico indicado y confirma la operación.
    public function destroy($id)
    {
        $tecnico = Tecnico::findOrFail($id);
        $tecnico->delete();

        return response()->json(['message' => 'Tecnico eliminado'], 200);
    }
}
