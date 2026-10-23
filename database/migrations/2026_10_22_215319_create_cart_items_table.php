<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crée la table cart_items pour le panier consommateur
     * Structure choisie : base de données (pas session) pour:
     * - Isolation entre utilisateurs (user_id)
     * - Persistance du panier
     * - Recalcul des prix depuis la base
     * - Validation du stock en temps réel
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            // Utilisateur consommateur (isolation du panier)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Stock distributeur visé (produit fini disponible)
            $table->foreignId('distributor_stock_id')->constrained('distributor_stocks')->cascadeOnDelete();
            // Quantité demandée par le consommateur
            $table->decimal('quantity', 10, 2)->default(1);
            // Prix unitaire au moment de l'ajout (pour historique, mais recalculé à chaque affichage)
            $table->decimal('unit_price_at_addition', 10, 2)->nullable();
            $table->timestamps();

            // Un utilisateur ne peut avoir le même produit qu'une fois dans son panier
            $table->unique(['user_id', 'distributor_stock_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
