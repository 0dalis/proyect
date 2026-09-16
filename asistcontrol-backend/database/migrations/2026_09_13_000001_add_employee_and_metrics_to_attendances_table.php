<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('user_id')->constrained('employees')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->after('employee_id')->constrained('shifts')->nullOnDelete();
            $table->integer('late_minutes')->default(0)->after('worked_minutes');
            $table->integer('early_minutes')->default(0)->after('late_minutes');
            $table->integer('overtime_minutes')->default(0)->after('early_minutes');
            $table->string('source', 20)->default('mobile')->after('overtime_minutes');

            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
            $table->dropConstrainedForeignId('shift_id');
            $table->dropColumn(['late_minutes', 'early_minutes', 'overtime_minutes', 'source']);
        });
    }
};
