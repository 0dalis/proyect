<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Carga masiva: un empleado puede existir sin oficina, turno, área ni tipo
 *   ("sin organizar") hasta que se le asignen desde "Organizar empleados".
 *   Mientras tanto no puede checar.
 * - El sueldo en México es por día: ya no se calculan horas trabajadas,
 *   solo el tiempo extra después de la salida del turno.
 * - La bitácora ya no guarda la IP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('office_id')->nullable()->change();
            $table->unsignedBigInteger('shift_id')->nullable()->change();
            $table->unsignedBigInteger('area_id')->nullable()->change();
            $table->string('employment_type')->nullable()->change();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('worked_minutes');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unsignedInteger('worked_minutes')->nullable()->after('minutes_early');
        });
    }
};
