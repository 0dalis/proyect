<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('date');
            $table->boolean('is_paid')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_holidays');
    }
};
