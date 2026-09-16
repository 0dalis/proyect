<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['bonus', 'deduction']);
            $table->enum('calculation', ['fixed', 'percentage', 'per_hour', 'per_day'])->default('fixed');
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_concepts');
    }
};
