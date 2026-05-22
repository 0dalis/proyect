<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                     // nombre
            $table->string('tipo')->nullable();         // tipo (ej. básico, premium, enterprise)
            $table->decimal('precio', 10, 2)->default(0); // costo
            $table->decimal('iva', 5, 2)->default(0);   // iva (porcentaje, ej. 16.00)
            $table->unsignedInteger('min_users')->default(1);  // min usuarios
            $table->unsignedInteger('max_users')->nullable();  // max usuarios (null = ilimitado)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
