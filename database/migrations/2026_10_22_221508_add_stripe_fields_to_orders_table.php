<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute les champs Stripe pour l'intégration Checkout
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('stripe_session_id')->nullable()->after('payment_reference');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->enum('stripe_status', ['created', 'pending', 'succeeded', 'failed', 'canceled'])->nullable();
            $table->bigInteger('stripe_amount')->nullable()->comment('Montant en centimes pour Stripe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['stripe_session_id', 'stripe_payment_intent_id', 'stripe_status', 'stripe_amount']);
        });
    }
};
