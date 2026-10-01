<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campana del panel: notificaciones de cada usuario.
 * - Dueño, administradores y gerentes: solicitudes que les toca revisar.
 * - Quien pidió algo: si se aprobó o rechazó, y por qué.
 * - Gerentes y empleados: los avisos de la empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            // Usuario de la BD central que la recibe
            $table->unsignedBigInteger('user_id');
            // request_submitted | request_approved | request_rejected | announcement
            $table->string('type', 40);
            $table->string('title', 150);
            $table->text('body')->nullable();
            // Ruta del panel a la que lleva, p. ej. /panel/solicitudes
            $table->string('link', 255)->nullable();
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_notifications');
    }
};
