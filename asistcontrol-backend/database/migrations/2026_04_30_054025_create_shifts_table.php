<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('office_id')->constrained()->onDelete('cascade');

            // Identidad
            $table->string('name'); // Matutino, Vespertino, etc.
            $table->boolean('is_active')->default(true);

            // Horarios base
            $table->time('start_time');
            $table->time('end_time');

            $table->boolean('cross_midnight')->default(false);

            // Comida
            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();

            // Tolerancias (más completas)
            $table->integer('tolerance_minutes')->default(10); // llegada tarde permitida
            $table->integer('early_leave_minutes')->default(0); // salida anticipada permitida

            // Control de horas
            $table->integer('work_hours_expected')->nullable(); // ej: 480 min (8h)

            $table->timestamps();
            $table->index(['office_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
