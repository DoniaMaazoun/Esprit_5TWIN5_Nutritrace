<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute quantity_reserved pour gérer les réservations Stripe
     * quantity_available = stock vendable (non réservé)
     * quantity_reserved = stock réservé pour commandes Stripe en attente de paiement
     */
    public function up(): void
    {
        Schema::table('distributor_stocks', function (Blueprint $table) {
            $table->decimal('quantity_reserved', 10, 2)->default(0)->after('quantity_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributor_stocks', function (Blueprint $table) {
            $table->dropColumn('quantity_reserved');
        });
    }
};
