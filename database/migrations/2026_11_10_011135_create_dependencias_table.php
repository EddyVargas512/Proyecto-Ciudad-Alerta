<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependencias', function (Blueprint $table) {
            $table->id('IdDependencia'); // Clave primaria personalizada
            $table->string('NombreDependencia', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependencias');
    }
};