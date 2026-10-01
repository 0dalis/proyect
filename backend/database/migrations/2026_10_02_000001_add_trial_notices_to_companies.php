<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avisos antes del primer cobro: 3 días antes y el día del cobro (una vez cada uno).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('trial_reminder_sent_at')->nullable()->after('trial_ends_at');
            $table->timestamp('trial_charge_notice_sent_at')->nullable()->after('trial_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['trial_reminder_sent_at', 'trial_charge_notice_sent_at']);
        });
    }
};
