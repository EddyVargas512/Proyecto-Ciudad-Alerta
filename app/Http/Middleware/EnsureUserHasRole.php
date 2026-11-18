<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // auth:sanctum valida la sesión; este middleware además comprueba el rol permitido.
        if (! $user instanceof Usuario || ! $user->hasRole(...$roles)) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        return $next($request);
    }
}
