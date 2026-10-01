<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('worked_days', 6, 2)->default(0);
            $table->integer('worked_minutes')->default(0);
            $table->integer('expected_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->integer('late_count')->default(0);
            $table->integer('absence_count')->default(0);
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('bonuses_amount', 12, 2)->default(0);
            $table->decimal('deductions_amount', 12, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
