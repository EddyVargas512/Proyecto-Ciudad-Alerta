<?php

namespace App\Console\Commands;

use App\Models\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateCiudadAlertaAdmin extends Command
{
    protected $signature = 'ciudad-alerta:crear-admin';

    protected $description = 'Crea la cuenta administradora inicial de Ciudad Alerta';

    public function handle(): int
    {
        // Solicita los datos en la terminal; la contraseña se captura sin mostrarla en pantalla.
        $name = trim((string) $this->ask('Nombre del administrador'));
        $email = Str::lower(trim((string) $this->ask('Correo del administrador')));
        $password = (string) $this->secret('Contraseña (mínimo 8 caracteres)');
        $passwordConfirmation = (string) $this->secret('Confirma la contraseña');

        // Valida formato, campos obligatorios, longitud y que el correo no esté registrado.
        $validator = Validator::make([
            'Nombre' => $name,
            'Email' => $email,
            'Contraseña' => $password,
            'ContraseñaConfirmada' => $passwordConfirmation,
        ], [
            'Nombre' => ['required', 'string', 'max:100'],
            'Email' => ['required', 'email', 'max:150', 'unique:usuarios,Email'],
            'Contraseña' => ['required', 'string', 'min:8'],
            'ContraseñaConfirmada' => ['same:Contraseña'],
        ]);

        if ($validator->fails()) {
            // Muestra cada error y termina sin guardar una cuenta incompleta.
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        // Reutiliza el rol Administrador si ya existe o lo crea antes que el usuario.
        $adminRole = TipoUsuario::firstOrCreate(['NombreTipo' => 'Administrador']);
        // Crea la cuenta vinculándola al rol administrador; Hash protege la contraseña.
        Usuario::create([
            'Nombre' => $name,
            'Email' => $email,
            'Contraseña' => Hash::make($password),
            'IdTipoUsuario' => $adminRole->IdTipoUsuario,
        ]);

        $this->info('Cuenta administradora creada. Inicia sesión desde /login.');

        return self::SUCCESS;
    }
}
