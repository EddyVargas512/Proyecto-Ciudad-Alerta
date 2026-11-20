<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoUsuario;
use Illuminate\Http\Request;

class TipoUsuarioController extends Controller
{
    // Devuelve los tipos de usuario registrados, como ciudadano y administrador.
    public function index()
    {
        return response()->json(TipoUsuario::latest()->get(), 200);
    }

    // Valida el nombre y crea un nuevo tipo de usuario.
    public function store(Request $request)
    {
        $data = $request->validate([
            'NombreTipo' => 'required|string|max:50'
        ]);

        $tipoUsuario = TipoUsuario::create($data);

        return response()->json($tipoUsuario, 201);
    }

    // Busca un tipo por ID; findOrFail genera una respuesta 404 si falta.
    public function show($id)
    {
        $tipoUsuario = TipoUsuario::findOrFail($id);
        return response()->json($tipoUsuario, 200);
    }

    // Actualiza los campos enviados, sin exigir que se envíe el objeto completo.
    public function update(Request $request, $id)
    {
        $tipoUsuario = TipoUsuario::findOrFail($id);

        $data = $request->validate([
            'NombreTipo' => 'sometimes|required|string|max:50'
        ]);

        $tipoUsuario->update($data);

        return response()->json($tipoUsuario, 200);
    }

    // Elimina el tipo de usuario seleccionado.
    public function destroy($id)
    {
        $tipoUsuario = TipoUsuario::findOrFail($id);
        $tipoUsuario->delete();

        return response()->json(['message' => 'Tipo de usuario eliminado'], 200);
    }
}
