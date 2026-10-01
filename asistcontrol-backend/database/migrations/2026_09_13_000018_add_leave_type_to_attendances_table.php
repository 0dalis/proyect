<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('leave_type', 20)->nullable()->after('status');
            $table->foreignId('request_id')->nullable()->after('leave_type')->constrained('requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('request_id');
            $table->dropColumn('leave_type');
        });
    }
};
