<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('IdUsuario'); // Clave primaria
            $table->string('Nombre', 100);
            $table->string('Email', 150)->unique();
            $table->string('Contraseña', 255);
            $table->unsignedBigInteger('IdTipoUsuario')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};