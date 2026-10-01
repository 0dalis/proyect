<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // El anual cuesta 11 mensualidades (un mes de regalo)
            $table->string('stripe_yearly_price_id')->nullable()->after('stripe_price_id');
            // Pre-nómina y bonos
            $table->boolean('includes_payroll')->default(false)->after('features');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('billing_interval', 10)->nullable()->after('plan_id');
            $table->timestamp('onboarding_accepted_at')->nullable()->after('activated_at');
            $table->timestamp('past_due_since')->nullable()->after('suspended_at');
            $table->timestamp('deletion_requested_at')->nullable()->after('past_due_since');
            $table->timestamp('deletion_scheduled_for')->nullable()->after('deletion_requested_at');
        });

        /*
         * Bajas de empresas. Mientras está en proceso guarda el correo del dueño
         * (para el enlace de exportación y el último aviso); al eliminar los
         * datos solo quedan datos de la empresa, sin datos personales.
         */
        Schema::create('company_deletions', function (Blueprint $table) {
            $table->id();
            // Sin FK: la empresa se borra al terminar el plazo
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('company_name');
            $table->string('last_plan');
            $table->string('billing_interval', 10)->nullable();
            // Relación con las facturas de Stripe (obligación fiscal)
            $table->string('stripe_customer_id')->nullable();
            $table->timestamp('registered_at');
            $table->timestamp('requested_at');
            $table->unsignedInteger('days_in_system');
            $table->text('reason');
            $table->timestamp('purge_after');
            $table->timestamp('purged_at')->nullable();

            // Solo durante el proceso
            $table->string('owner_name')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('export_path')->nullable();
            $table->string('export_token_hash', 64)->nullable()->unique();
            $table->timestamp('export_expires_at')->nullable();
            $table->unsignedSmallInteger('export_links_sent')->default(0);
            $table->timestamp('export_downloaded_at')->nullable();

            // Valoración (enlace único en el último correo)
            $table->string('feedback_token', 64)->nullable()->unique();
            $table->unsignedTinyInteger('feedback_rating')->nullable();
            $table->text('feedback_comment')->nullable();
            $table->timestamp('feedback_submitted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_deletions');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['billing_interval', 'onboarding_accepted_at', 'past_due_since', 'deletion_requested_at', 'deletion_scheduled_for']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['stripe_yearly_price_id', 'includes_payroll']);
        });
    }
};
