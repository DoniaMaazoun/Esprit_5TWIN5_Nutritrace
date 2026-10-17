<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 3 - Expéditions (livraisons) du distributeur
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Lot provenant des modules 1/2 (table "batches", pas encore créée) :
            // la clé étrangère sera ajoutée quand le module 1 sera fusionné
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->string('product_name');
            $table->decimal('weight_kg', 12, 2);
            $table->string('origin_address');
            $table->foreignId('point_of_sale_id')->nullable()->constrained('points_of_sale')->nullOnDelete();
            // Statut : planned, in_transit, delivered, cancelled
            $table->string('status')->default('planned');
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            // Totaux recalculés à partir des étapes
            $table->decimal('total_distance_km', 12, 2)->default(0);
            $table->decimal('total_co2_kg', 12, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
