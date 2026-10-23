<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crée la table orders pour les commandes consommateur
     * Structure: numéro unique, utilisateur, livraison, paiement, statuts
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Numéro de commande unique (ex: CMD-20261022-001234)
            $table->string('order_number')->unique();
            // Utilisateur consommateur (isolation)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Montant total de la commande (recalculé depuis les lignes)
            $table->decimal('total_amount', 10, 2)->default(0);
            // Devise (DT par défaut pour Tunisie)
            $table->string('currency', 3)->default('TND');
            
            // Informations de livraison
            $table->string('delivery_name')->nullable();
            $table->string('delivery_phone')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_governorate')->nullable();
            $table->text('delivery_notes')->nullable();
            
            // Mode de paiement (cash_on_delivery pour le moment, extensible)
            $table->enum('payment_method', ['cash_on_delivery', 'credit_card', 'bank_transfer'])->default('cash_on_delivery');
            // Statut du paiement
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            // Référence de transaction (pour intégration future avec prestataire)
            $table->string('payment_reference')->nullable();
            
            // Statut de la commande
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            // Notes internes
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
