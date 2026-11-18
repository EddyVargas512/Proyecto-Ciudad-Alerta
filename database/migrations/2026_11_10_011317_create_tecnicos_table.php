<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tecnicos', function (Blueprint $table) {
            $table->id('IdTecnico'); // Clave primaria personalizada
            $table->string('Nombre', 150);
            $table->string('Email', 150)->unique();
            $table->unsignedBigInteger('IdDependencia'); // Llave foránea hacia dependencias
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tecnicos');
    }
};