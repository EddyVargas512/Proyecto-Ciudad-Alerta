<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    // Habilita la creación de tokens de acceso para la API mediante Sanctum.
    use HasApiTokens;

    // Configura la tabla, la clave primaria y los campos permitidos del usuario.
    protected $table = 'usuarios';
    protected $primaryKey = 'IdUsuario';

    protected $fillable = [
        'Nombre',
        'Email',
        'Contraseña',
        'IdTipoUsuario'
    ];

    // Evita que la contraseña aparezca al convertir el usuario a JSON.
    protected $hidden = [
        'Contraseña'
    ];

    protected function casts(): array
    {
        return [
            // Laravel convierte la contraseña a un hash seguro antes de almacenarla.
            'Contraseña' => 'hashed',
        ];
    }

    public function getAuthPasswordName(): string
    {
        // Indica a Laravel que la columna de contraseña tiene nombre personalizado.
        return 'Contraseña';
    }

    public function tipoUsuario(): BelongsTo
    {
        // Obtiene el rol asociado al usuario, por ejemplo Ciudadano o Administrador.
        return $this->belongsTo(TipoUsuario::class, 'IdTipoUsuario', 'IdTipoUsuario');
    }

    public function hasRole(string ...$roles): bool
    {
        // Compara el rol guardado con los roles aceptados por una ruta o acción.
        $currentRole = $this->tipoUsuario?->NombreTipo;
        if (! $currentRole) {
            return false;
        }

        // Normaliza nombres para aceptar variantes como "Admin" y "Administrador".
        $normalize = static function (string $role): string {
            $role = Str::slug($role);
            return $role === 'admin' ? 'administrador' : $role;
        };

        return in_array($normalize($currentRole), array_map($normalize, $roles), true);
    }
}
