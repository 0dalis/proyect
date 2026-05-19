<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // Identidad
            $table->string('name');
            $table->string('code')->unique(); // Ej: EMP-001
            $table->string('slug')->unique(); // Para URLs o subdominios: empresa-x

            // Plan / Suscripción
            $table->enum('plan', ['free', 'growth', 'business', 'enterprise'])->default('free');
            $table->timestamp('trial_ends_at')->nullable(); // Para pruebas gratis
            $table->timestamp('subscription_ends_at')->nullable(); // Control de pagos

            // Configuración técnica
            $table->boolean('has_dedicated_db')->default(false);

            // Branding
            $table->string('custom_styles_path')->nullable();
            $table->string('logo_path')->nullable();

            // Estado
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->index('is_active');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
