<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_estados', function (Blueprint $table) {
            $table->id('IdHistorial'); // Clave primaria personalizada
            $table->unsignedBigInteger('IdIncidencia');
            $table->string('EstadoAnterior', 50);
            $table->string('EstadoNuevo', 50);
            $table->unsignedBigInteger('IdUsuario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estados');
    }
};