<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    // Devuelve todas las categorías para que la aplicación pueda mostrarlas en sus formularios.
    public function index()
    {
        return response()->json(Categoria::latest()->get(), 200);
    }

    // Comprueba que llegue un nombre válido antes de guardar una categoría nueva.
    public function store(Request $request)
    {
        $data = $request->validate([
            'NombreCategoria' => 'required|string|max:100'
        ]);

        $categoria = Categoria::create($data);

        return response()->json($categoria, 201);
    }

    // Busca la categoría solicitada; findOrFail responde con error 404 si no existe.
    public function show($id)
    {
        $categoria = Categoria::findOrFail($id);
        return response()->json($categoria, 200);
    }

    // Permite cambiar solo los campos enviados y valida el nombre si viene en la petición.
    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $data = $request->validate([
            'NombreCategoria' => 'sometimes|required|string|max:100'
        ]);

        $categoria->update($data);

        return response()->json($categoria, 200);
    }

    // Elimina la categoría encontrada y confirma la operación en formato JSON.
    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();

        return response()->json(['message' => 'Categoria eliminada'], 200);
    }
}
