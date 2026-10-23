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
     * Permet aux expéditions de transporter des produits finis (ProductionLot)
     * en plus des récoltes brutes (Harvest)
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Nullable pour supporter à la fois Harvest et ProductionLot
            // Un shipment peut transporter soit une récolte brute, soit un produit fini
            $table->foreignId('production_lot_id')->nullable()->after('harvest_id')
                ->constrained('production_lots')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_lot_id');
        });
    }
};
