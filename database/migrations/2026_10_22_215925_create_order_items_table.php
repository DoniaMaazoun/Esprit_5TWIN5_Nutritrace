<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crée la table order_items pour les lignes de commande
     * Relie une commande aux stocks distributeurs avec quantités et prix figés
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            // Commande parente
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // Stock distributeur visé (produit fini au moment de la commande)
            $table->foreignId('distributor_stock_id')->constrained('distributor_stocks')->cascadeOnDelete();
            // Quantité commandée
            $table->decimal('quantity', 10, 2)->default(1);
            // Prix unitaire au moment de la commande (figé, pas recalculé)
            $table->decimal('unit_price', 10, 2)->default(0);
            // Sous-total de la ligne
            $table->decimal('subtotal', 10, 2)->default(0);
            // Nom du produit au moment de la commande (pour historique)
            $table->string('product_name')->nullable();
            // Nom du point de vente au moment de la commande
            $table->string('point_of_sale_name')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
