<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('office_id')->constrained()->onDelete('cascade');

            $table->date('date');

            // Resultado final del día
            $table->enum('status', [
                'present',
                'late',
                'absent',
                'justified'
            ])->default('present');

            $table->integer('worked_minutes')->nullable();

            $table->timestamps();
            $table->unique(['user_id', 'date']);
            $table->index(['office_id', 'date']);
            $table->index(['user_id', 'status']);
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->index(['company_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
