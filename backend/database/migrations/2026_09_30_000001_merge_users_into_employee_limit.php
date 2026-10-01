<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El plan solo limita empleados: cada empleado puede tener (o no) usuario para
 * la app, sin costo aparte. Se quitan los "usuarios incluidos / extra".
 *
 * Además: código único de empresa (para entrar a la app) y contraseña temporal
 * que el empleado debe cambiar en su primer acceso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['included_users', 'extra_user_price', 'stripe_extra_user_price_id']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('extra_users');
            $table->string('code', 12)->nullable()->unique()->after('slug');
        });

        DB::table('companies')->whereNull('code')->orderBy('id')->each(function ($company) {
            DB::table('companies')->where('id', $company->id)->update(['code' => Company::generateCode()]);
        });

        Schema::table('users', function (Blueprint $table) {
            // Contraseña temporal: al entrar debe cambiarla y crear su PIN
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
            $table->unsignedInteger('extra_users')->default(0);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('included_users')->default(3);
            $table->decimal('extra_user_price', 10, 2)->default(0);
            $table->string('stripe_extra_user_price_id')->nullable();
        });
    }
};
