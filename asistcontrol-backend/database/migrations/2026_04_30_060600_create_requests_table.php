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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->enum('type', [
                'permission',
                'justification',
                'vacation'
            ]);

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->text('reason')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->boolean('is_paid')->default(false); // goce de sueldo

            // Relación opcional con asistencia
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('attachment_path')->nullable();
            $table->index(['company_id', 'status']);
            $table->index(['user_id', 'type']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
