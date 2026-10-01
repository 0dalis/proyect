<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('orientation', ['horizontal', 'vertical'])->default('horizontal');
            $table->json('design')->nullable();

            $table->string('qr_token', 64)->nullable()->unique();
            $table->text('qr_payload')->nullable();

            $table->string('photo_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->boolean('download_enabled')->default(false);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('last_printed_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_credentials');
    }
};
