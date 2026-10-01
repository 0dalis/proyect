<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('office_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('area_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            $table->string('employee_code');
            $table->string('first_name');
            $table->string('last_name');
            $table->text('pin');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
