<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - 2FA de los Super Admins (secreto de la app y códigos de recuperación, cifrados).
 * - Bitácora de lo que hace cada Super Admin.
 * - Opiniones de dueños y administradores (1 a 5 estrellas, con medias).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->text('app_authentication_secret')->nullable()->after('password');
            $table->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
        });

        Schema::create('super_admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->nullable()->constrained()->nullOnDelete();
            // Se guarda el nombre por si después se elimina al Super Admin
            $table->string('super_admin_name')->nullable();
            $table->string('action', 60)->index();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // 1.0 a 5.0 en pasos de media estrella
            $table->decimal('score', 2, 1);
            $table->text('comment')->nullable();
            // El usuario acepta que su opinión aparezca en la página con su nombre y empresa
            $table->boolean('allow_publish')->default(false);
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('super_admin_audit_logs');

        Schema::table('super_admins', function (Blueprint $table) {
            $table->dropColumn(['app_authentication_secret', 'app_authentication_recovery_codes']);
        });
    }
};
