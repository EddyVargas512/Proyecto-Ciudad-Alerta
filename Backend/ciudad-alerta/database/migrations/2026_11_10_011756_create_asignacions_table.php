<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->id('IdAsignacion'); // Clave primaria personalizada
            $table->unsignedBigInteger('IdIncidencia');
            $table->unsignedBigInteger('IdTecnico');
            $table->timestamp('FechaAsignacion')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
    }
};