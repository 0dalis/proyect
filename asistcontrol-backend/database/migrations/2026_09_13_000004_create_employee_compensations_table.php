<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('salary_type', ['fixed', 'hourly'])->default('fixed');
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->enum('pay_frequency', ['weekly', 'biweekly', 'monthly'])->default('monthly');
            $table->decimal('daily_hours', 5, 2)->default(8);
            $table->decimal('overtime_factor', 4, 2)->default(1.5);
            $table->integer('overtime_cap_minutes')->nullable();
            $table->char('currency', 3)->default('MXN');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};
