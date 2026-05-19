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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Multiempresa
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Quién la creó
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Contenido
            $table->string('title');
            $table->text('message');

            // Segmentación
            $table->enum('target_type', ['all', 'area', 'user'])->default('all');

            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Tiempo
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Control
            $table->boolean('is_active')->default(true);
            $table->enum('priority', ['normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('scheduled_at')->nullable();

            $table->timestamps();
            $table->index(['company_id', 'is_active']);
            $table->index(['target_type', 'area_id']);
            $table->index(['sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
