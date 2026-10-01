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
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('color', 7)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // Radio de geocerca en metros (10 a 100)
            $table->unsignedSmallInteger('geofence_radius')->default(50);
            $table->string('timezone')->default('America/Mexico_City');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->time('starts_at');
            $table->time('ends_at');
            // Días ISO activos: 1 = lunes ... 7 = domingo
            $table->json('weekdays');
            $table->unsignedSmallInteger('tolerance_minutes')->default(15);
            // Después de este límite la entrada cuenta como falta
            $table->unsignedSmallInteger('absence_after_minutes')->default(30);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('employee_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('position')->nullable();
            $table->foreignId('office_id')->constrained();
            $table->foreignId('shift_id')->constrained();
            $table->foreignId('area_id')->constrained();
            $table->string('status')->default('active');
            $table->string('employment_type')->default('permanent');
            $table->string('work_mode')->default('onsite');
            $table->date('hired_on')->nullable();
            $table->date('contract_ends_on')->nullable();
            $table->date('terminated_on')->nullable();
            $table->string('pin_hash')->nullable();
            $table->string('badge_token', 64)->nullable()->unique();
            $table->timestamp('badge_issued_at')->nullable();
            $table->date('badge_expires_on')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('salary_period')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_number']);
        });

        // Áreas que supervisa un gerente (además de la suya)
        Schema::create('area_manager', function (Blueprint $table) {
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->primary(['area_id', 'employee_id']);
        });

        // Días de home office en los que no se valida la geocerca
        Schema::create('remote_work_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            // null = todos los días del rango; si no, solo esos días ISO
            $table->json('weekdays')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_work_periods');
        Schema::dropIfExists('area_manager');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('offices');
        Schema::dropIfExists('areas');
    }
};
