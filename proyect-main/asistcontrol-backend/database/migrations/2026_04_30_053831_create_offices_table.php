<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Identidad
            $table->string('name');
            $table->string('code')->nullable(); // Ej: CDMX-01
            $table->boolean('is_active')->default(true);

            // Geolocalización
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius_meters')->default(100);

            // Zona horaria
            $table->string('timezone')->default('America/Mexico_City');

            $table->timestamps();
            $table->index(['company_id', 'is_active']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
