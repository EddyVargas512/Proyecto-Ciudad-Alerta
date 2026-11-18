<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    // GET /api/usuarios (Listar)
    public function index()
    {
        // Obtiene los usuarios y los entrega como una lista JSON.
        return response()->json(Usuario::all(), 200);
    }

    // POST /api/usuarios (Crear)
    public function store(Request $request)
    {
        // Valida los datos obligatorios y evita registrar un correo repetido.
        $data = $request->validate([
            'Nombre'        => 'required|string|max:100',
            'Email'         => 'required|email|unique:usuarios,Email',
            'Contraseña'    => 'required|string|min:6',
            'IdTipoUsuario' => 'required|integer'
        ]);

        // Nunca se guarda la contraseña escrita: se transforma en un hash irreversible.
        $data['Contraseña'] = bcrypt($data['Contraseña']);

        $usuario = Usuario::create($data);

        return response()->json($usuario, 201);
    }

    // GET /api/usuarios/{id} (Ver uno)
    public function show($id)
    {
        // Busca por clave primaria y devuelve 404 automáticamente si no existe.
        $usuario = Usuario::findOrFail($id);
        return response()->json($usuario, 200);
    }

    // PUT /api/usuarios/{id} (Actualizar)
    public function update(Request $request, $id)
    {
        // Primero confirma que el usuario exista antes de intentar modificarlo.
        $usuario = Usuario::findOrFail($id);

        // 'sometimes' permite actualizar solo algunos campos sin enviar el resto.
        $data = $request->validate([
            'Nombre'        => 'sometimes|required|string|max:100',
            'Email'         => 'sometimes|required|email|unique:usuarios,Email,' . $id . ',IdUsuario',
            'IdTipoUsuario' => 'sometimes|required|integer'
        ]);

        $usuario->update($data);

        return response()->json($usuario, 200);
    }

    // DELETE /api/usuarios/{id} (Eliminar)
    public function destroy($id)
    {
        // Localiza el usuario, lo elimina y devuelve una respuesta de confirmación.
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado'], 200);
    }
}
