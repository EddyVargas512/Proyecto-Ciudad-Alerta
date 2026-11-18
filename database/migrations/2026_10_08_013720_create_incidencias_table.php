<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id('IdIncidencia'); // Clave primaria
            $table->string('Titulo', 150);
            $table->text('Descripcion');
            $table->string('UbicacionTexto', 255)->nullable();
            $table->decimal('Latitud', 10, 8);
            $table->decimal('Longitud', 11, 8);
            $table->string('Estado', 50)->default('Pendiente');
            $table->integer('CantidadApoyos')->default(0);
            
            // Relaciones / Claves foráneas
            $table->unsignedBigInteger('IdUsuario');
            $table->unsignedBigInteger('IdCategoria');
            $table->unsignedBigInteger('IdPrioridad')->default(1);
            $table->unsignedBigInteger('IdZona')->default(1);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};