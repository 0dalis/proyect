<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horario especial por día dentro de un turno. Ej.: lunes a jueves de 9 a 18
 * y el viernes de 9 a 17. Los días sin horario especial usan el del turno.
 *
 * {"5": {"starts_at": "09:00", "ends_at": "17:00", "break_minutes": 60}}
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->json('day_schedules')->nullable()->after('weekdays');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('day_schedules');
        });
    }
};
