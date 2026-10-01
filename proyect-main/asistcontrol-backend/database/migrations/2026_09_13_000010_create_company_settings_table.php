<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('currency', 3)->default('MXN');
            $table->string('timezone')->default('America/Mexico_City');
            $table->enum('default_pay_frequency', ['weekly', 'biweekly', 'monthly'])->default('monthly');
            $table->boolean('attendance_bonus_enabled')->default(false);
            $table->decimal('attendance_bonus_amount', 12, 2)->default(0);
            $table->decimal('late_penalty_amount', 12, 2)->default(0);
            $table->decimal('absence_penalty_amount', 12, 2)->default(0);
            $table->boolean('overtime_enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
