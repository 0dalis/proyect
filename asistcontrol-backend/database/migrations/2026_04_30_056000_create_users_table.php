<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('office_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('area_id')->nullable()->constrained()->onDelete('set null');

            // Datos personales
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');

            // Seguridad
            $table->text('pin')->unique(); // PIN fijo de 8 dígitos
            $table->string('employee_code')->unique(); // QR o código rápido

            // Firebase
            $table->text('device_token')->nullable();

            // Estado
            $table->boolean('is_active')->default(true);

            $table->rememberToken();
            $table->timestamps();
            $table->unique(['company_id', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
