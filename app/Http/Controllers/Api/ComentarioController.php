<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comentario;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    // Devuelve los comentarios guardados.
    public function index()
    {
        return response()->json(Comentario::latest()->get(), 200);
    }

    // Verifica que existan el reporte y el usuario, y que el texto no esté vacío.
    public function store(Request $request)
    {
        $data = $request->validate([
            'IdIncidencia' => 'required|exists:incidencias,IdIncidencia',
            'IdUsuario' => 'required|exists:usuarios,IdUsuario',
            'TextoComentario' => 'required|string'
        ]);

        $comentario = Comentario::create($data);

        return response()->json($comentario, 201);
    }

    // Recupera un comentario concreto o responde 404 si no está registrado.
    public function show($id)
    {
        $comentario = Comentario::findOrFail($id);
        return response()->json($comentario, 200);
    }

    // Actualiza solo los campos enviados después de validarlos.
    public function update(Request $request, $id)
    {
        $comentario = Comentario::findOrFail($id);

        $data = $request->validate([
            'IdIncidencia' => 'sometimes|required|exists:incidencias,IdIncidencia',
            'IdUsuario' => 'sometimes|required|exists:usuarios,IdUsuario',
            'TextoComentario' => 'sometimes|required|string'
        ]);

        $comentario->update($data);

        return response()->json($comentario, 200);
    }

    // Elimina el comentario seleccionado y confirma el resultado.
    public function destroy($id)
    {
        $comentario = Comentario::findOrFail($id);
        $comentario->delete();

        return response()->json(['message' => 'Comentario eliminado'], 200);
    }
}
