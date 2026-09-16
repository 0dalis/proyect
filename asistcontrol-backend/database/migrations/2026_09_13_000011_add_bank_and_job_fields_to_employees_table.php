<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('pin');
            $table->string('bank_account', 30)->nullable()->after('bank_name');
            $table->string('position')->nullable()->after('bank_account');
            $table->date('hired_at')->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account', 'position', 'hired_at']);
        });
    }
};
