<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ampliar el enum de destino (MySQL)
        DB::statement("ALTER TABLE notifications MODIFY target_type ENUM('all','area','user','office','users') NOT NULL DEFAULT 'all'");

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('office_id')->nullable()->after('area_id')->constrained('offices')->nullOnDelete();
            $table->json('target_user_ids')->nullable()->after('target_user_id');
            $table->unsignedInteger('sent_count')->default(0)->after('is_active');
            $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_id');
            $table->dropColumn(['target_user_ids', 'sent_count', 'failed_count']);
        });

        DB::statement("ALTER TABLE notifications MODIFY target_type ENUM('all','area','user') NOT NULL DEFAULT 'all'");
    }
};
