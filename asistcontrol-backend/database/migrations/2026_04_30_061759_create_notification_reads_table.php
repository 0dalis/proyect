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
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notification_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Multiempresa (para indexar mejor consultas grandes)
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Lectura
            $table->timestamp('read_at');

            $table->timestamps();

            // 🔥 Índices IMPORTANTES
            $table->index(['company_id', 'notification_id']);
            $table->index(['user_id', 'notification_id']);

            // Evitar duplicados
            $table->unique(['notification_id', 'user_id']);
            $table->index(['notification_id', 'read_at']);
            $table->index(['company_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
