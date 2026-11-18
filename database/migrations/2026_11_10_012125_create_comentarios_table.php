<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comentarios', function (Blueprint $table) {
            $table->id('IdComentario'); // Clave primaria personalizada
            $table->unsignedBigInteger('IdIncidencia');
            $table->unsignedBigInteger('IdUsuario');
            $table->text('TextoComentario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};