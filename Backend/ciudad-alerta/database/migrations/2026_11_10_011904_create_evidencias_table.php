<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias', function (Blueprint $table) {
            $table->id('IdEvidencia'); // Clave primaria personalizada
            $table->unsignedBigInteger('IdIncidencia');
            $table->string('RutaArchivo', 255);
            $table->string('TipoArchivo', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias');
    }
};
