<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        // Validamos el formulario antes de guardar; las cuentas nuevas siempre son ciudadanas.
        $data = $request->validate([
            'Nombre' => ['required', 'string', 'max:100'],
            'Email' => ['required', 'email', 'max:150', 'unique:usuarios,Email'],
            'Contraseña' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $citizenRole = TipoUsuario::firstOrCreate(['NombreTipo' => 'Ciudadano']);
        $user = Usuario::create([
            'Nombre' => $data['Nombre'],
            'Email' => Str::lower($data['Email']),
            'Contraseña' => $data['Contraseña'],
            'IdTipoUsuario' => $citizenRole->IdTipoUsuario,
        ]);

        return $this->authenticatedResponse($user, 201);
    }

    public function login(Request $request, RateLimiter $limiter): JsonResponse
    {
        $data = $request->validate([
            'Email' => ['required', 'email'],
            'Contraseña' => ['required', 'string'],
        ]);

        $email = Str::lower($data['Email']);
        $key = Str::transliterate(Str::lower($email.'|'.$request->ip()));

        // Limita intentos fallidos por correo e IP para frenar pruebas repetidas de contraseñas.
        if ($limiter->tooManyAttempts('login:'.$key, 5)) {
            return response()->json([
                'message' => 'Demasiados intentos. Intenta de nuevo en un minuto.',
                'retry_after' => $limiter->availableIn('login:'.$key),
            ], 429);
        }

        $user = Usuario::query()->where('Email', $email)->first();

        if (! $user || ! Hash::check($data['Contraseña'], $user->Contraseña)) {
            $limiter->hit('login:'.$key, 60);
            return response()->json(['message' => 'El correo o la contraseña no son correctos.'], 401);
        }

        $limiter->clear('login:'.$key);

        return $this->authenticatedResponse($user);
    }

    // Equivale a "ver perfil" en la guía: Sanctum identifica al usuario desde el token Bearer.
    public function me(Request $request): JsonResponse
    {
        return response()->json(['usuario' => $this->userData($request->user())]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Usuario $user */
        $user = $request->user();
        $data = $request->validate([
            'Nombre' => ['required', 'string', 'max:100'],
            'Email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'Email')->ignore($user->IdUsuario, 'IdUsuario')],
        ]);

        // Solo se aceptan nombre y correo; el cliente no puede cambiar su propio rol.
        $user->update([
            'Nombre' => $data['Nombre'],
            'Email' => Str::lower($data['Email']),
        ]);

        return response()->json([
            'message' => 'El perfil se actualizó correctamente.',
            'usuario' => $this->userData($user->fresh()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    private function authenticatedResponse(Usuario $user, int $status = 200): JsonResponse
    {
        // El token se entrega a React y se usará en las siguientes peticiones protegidas.
        $token = $user->createToken('ciudad-alerta-web', ['*'], now()->addHours(12))->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => $this->userData($user),
        ], $status);
    }

    private function userData(Usuario $user): array
    {
        $user->loadMissing('tipoUsuario');

        return [
            'IdUsuario' => $user->IdUsuario,
            'Nombre' => $user->Nombre,
            'Email' => $user->Email,
            'IdTipoUsuario' => $user->IdTipoUsuario,
            'TipoUsuario' => $user->tipoUsuario?->NombreTipo,
            'EsAdministrador' => $user->hasRole('Administrador'),
        ];
    }
}
