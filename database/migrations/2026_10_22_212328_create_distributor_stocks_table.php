<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Stocks de produits finis disponibles chez les distributeurs
     * Alimenté par les livraisons (Shipment) avec production_lot_id
     */
    public function up(): void
    {
        Schema::create('distributor_stocks', function (Blueprint $table) {
            $table->id();
            // Point de vente du distributeur
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->cascadeOnDelete();
            // Produit fini (lot de production)
            $table->foreignId('production_lot_id')->constrained('production_lots')->cascadeOnDelete();
            // Quantité disponible à la vente
            $table->decimal('quantity_available', 10, 2)->default(0);
            // Prix de vente unitaire (en DT ou autre devise)
            $table->decimal('unit_price', 10, 2)->nullable();
            // Statut du stock
            $table->enum('status', ['available', 'reserved', 'out_of_stock'])->default('available');
            // Date de réception chez le distributeur
            $table->date('received_at')->nullable();
            $table->timestamps();

            // Un même lot ne peut être stocké qu'une fois par point de vente
            $table->unique(['point_of_sale_id', 'production_lot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_stocks');
    }
};
