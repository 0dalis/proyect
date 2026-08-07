<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->json('caracteristicas')->nullable()->after('max_users');
            $table->string('stripe_price_id')->nullable()->after('caracteristicas');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['caracteristicas', 'stripe_price_id']);
        });
    }
};
