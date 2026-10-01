<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('concept');
            $table->decimal('total_amount', 12, 2);
            $table->unsignedSmallInteger('installments')->default(1);
            $table->unsignedSmallInteger('paid_installments')->default(0);
            $table->decimal('installment_amount', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->enum('status', ['active', 'paid', 'cancelled'])->default('active');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
