<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Días festivos de la empresa (no cuentan como falta; trabajados se pagan doble adicional).
 * - Descanso del turno, horas trabajadas y tiempo extra de cada jornada.
 * - Periodos de pre-nómina cerrados: el cálculo queda congelado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->date('date');
            $table->string('name');
            // Descanso obligatorio de la LFT (art. 74) o día que da la empresa
            $table->boolean('is_official')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'date']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            // Comida o descanso sin goce: se descuenta de las horas trabajadas
            $table->unsignedSmallInteger('break_minutes')->default(0)->after('ends_at');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            // Se calculan al registrar la salida
            $table->unsignedInteger('worked_minutes')->nullable()->after('minutes_early');
            $table->unsignedInteger('overtime_minutes')->default(0)->after('worked_minutes');
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->string('closed_by_name')->nullable();
            $table->timestamp('closed_at');
            // Totales del periodo al cerrarlo
            $table->json('totals');
            $table->timestamps();

            $table->index(['company_id', 'starts_on', 'ends_on']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            // Fila completa de la pre-nómina tal como estaba al cerrar
            $table->json('data');
            $table->decimal('total', 12, 2);
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_periods');

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['worked_minutes', 'overtime_minutes']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('break_minutes');
        });

        Schema::dropIfExists('holidays');
    }
};
