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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');

            $table->enum('type', [
                'check_in',
                'check_out',
                'lunch_start',
                'lunch_end'
            ]);

            $table->timestamp('recorded_at');

            // Geolocalización
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Evidencia
            $table->string('photo_path')->nullable();

            $table->timestamps();
            $table->index(['attendance_id', 'type']);
            $table->index('recorded_at');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->index(['user_id', 'recorded_at']);
            $table->unique(['attendance_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
