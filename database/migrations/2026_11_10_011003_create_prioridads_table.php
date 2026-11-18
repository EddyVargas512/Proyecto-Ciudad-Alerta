<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prioridades', function (Blueprint $table) {
            $table->id('IdPrioridad'); // Clave primaria personalizada
            $table->string('NivelPrioridad', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prioridades');
    }
};