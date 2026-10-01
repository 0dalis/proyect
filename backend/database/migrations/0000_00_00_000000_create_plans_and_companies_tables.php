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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // basic = pool compartido Free/Básico, plus = pool Plus, premium = BD dedicada
            $table->string('database_tier')->default('basic');
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->unsignedInteger('included_employees')->default(10);
            $table->unsignedInteger('included_offices')->default(1);
            $table->unsignedInteger('included_users')->default(3);
            $table->unsignedInteger('employee_block_size')->default(10);
            $table->decimal('employee_block_price', 10, 2)->default(0);
            $table->decimal('extra_office_price', 10, 2)->default(0);
            $table->decimal('extra_user_price', 10, 2)->default(0);
            // null = usa los días de prueba globales (system_settings)
            $table->unsignedSmallInteger('trial_days')->nullable();
            $table->json('features')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_employee_block_price_id')->nullable();
            $table->string('stripe_extra_office_price_id')->nullable();
            $table->string('stripe_extra_user_price_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('plan_id')->constrained();
            $table->string('status')->default('pending');
            $table->string('database')->nullable();
            $table->unsignedInteger('extra_employee_blocks')->default(0);
            $table->unsignedInteger('extra_offices')->default(0);
            $table->unsignedInteger('extra_users')->default(0);
            $table->boolean('employees_can_use_web')->default(false);
            $table->boolean('payroll_enabled')->default(false);
            $table->boolean('bonuses_enabled')->default(false);
            $table->string('timezone')->default('America/Mexico_City');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();

            // Laravel Cashier (la empresa es el cliente de Stripe)
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();
        });

        Schema::create('super_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('super_admins');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('plans');
    }
};
