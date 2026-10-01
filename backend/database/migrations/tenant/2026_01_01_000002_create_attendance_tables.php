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
        Schema::create('kiosks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        // Celulares registrados para checar con biometría
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('device_identifier');
            $table->string('name')->nullable();
            $table->string('platform')->nullable();
            // Llave pública (PEM) generada en Keystore / Secure Enclave
            $table->text('public_key');
            $table->string('push_token')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'device_identifier']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->constrained();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->string('type');
            $table->timestamp('recorded_at');
            $table->string('channel');
            $table->string('status');
            $table->unsignedInteger('minutes_late')->default(0);
            $table->unsignedInteger('minutes_early')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->boolean('geofence_skipped')->default(false);
            $table->string('photo_path')->nullable();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('kiosk_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_justified')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'work_date']);
        });

        // Justificaciones, avisos de llegada tarde / salida anticipada, vacaciones y permisos
        Schema::create('employee_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->time('expected_time')->nullable();
            $table->text('reason');
            $table->foreignId('attendance_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('author_user_id');
            $table->string('title');
            $table->text('body');
            $table->string('link_url')->nullable();
            $table->string('image_path')->nullable();
            // all | areas | offices | roles | employees
            $table->string('audience_type')->default('all');
            $table->json('audience_ids')->nullable();
            $table->boolean('show_on_kiosk')->default(false);
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcement_employee', function (Blueprint $table) {
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->primary(['announcement_id', 'employee_id']);
        });

        Schema::create('bonus_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('period')->default('biweekly');
            $table->string('amount_type')->default('fixed');
            $table->decimal('amount', 12, 2);
            // [{"metric":"unjustified_lates","operator":"<","value":3}]
            $table->json('conditions');
            // null = todos los empleados; si no, ids específicos
            $table->json('employee_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_rules');
        Schema::dropIfExists('announcement_employee');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('employee_requests');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('kiosks');
    }
};
