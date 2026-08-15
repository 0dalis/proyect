<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_offices')->nullable()->after('max_users');
            $table->decimal('annual_price', 10, 2)->nullable()->after('precio');
            $table->decimal('per_extra_user_price', 10, 2)->nullable()->after('annual_price');
            $table->decimal('per_extra_office_price', 10, 2)->nullable()->after('per_extra_user_price');
            $table->json('features')->nullable()->after('caracteristicas');
            $table->string('stripe_annual_price_id')->nullable()->after('stripe_price_id');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'max_offices',
                'annual_price',
                'per_extra_user_price',
                'per_extra_office_price',
                'features',
                'stripe_annual_price_id',
            ]);
        });
    }
};
