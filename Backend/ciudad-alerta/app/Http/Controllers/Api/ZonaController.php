<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use Illuminate\Http\Request;

class ZonaController extends Controller
{
    // Lista las zonas registradas para usarlas en la aplicación.
    public function index()
    {
        return response()->json(Zona::latest()->get(), 200);
    }

    // Valida el nombre antes de crear una nueva zona.
    public function store(Request $request)
    {
        $data = $request->validate([
            'NombreZona' => 'required|string|max:100'
        ]);

        $zona = Zona::create($data);

        return response()->json($zona, 201);
    }

    // Busca una zona por ID; si no existe, Laravel responde con 404.
    public function show($id)
    {
        $zona = Zona::findOrFail($id);
        return response()->json($zona, 200);
    }

    // Cambia los campos enviados después de validar sus valores.
    public function update(Request $request, $id)
    {
        $zona = Zona::findOrFail($id);

        $data = $request->validate([
            'NombreZona' => 'sometimes|required|string|max:100'
        ]);

        $zona->update($data);

        return response()->json($zona, 200);
    }

    // Elimina la zona y devuelve una confirmación.
    public function destroy($id)
    {
        $zona = Zona::findOrFail($id);
        $zona->delete();

        return response()->json(['message' => 'Zona eliminada'], 200);
    }
}
