<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 3 - Étapes de transport d'une expédition (multimodal)
     */
    public function up(): void
    {
        Schema::create('shipment_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transport_mode_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->string('from_location');
            $table->string('to_location');
            $table->decimal('distance_km', 10, 2);
            $table->decimal('weight_kg', 12, 2);
            // Copie du facteur au moment du calcul pour garder l'historique
            $table->decimal('emission_factor', 10, 5);
            // CO2 = distance (km) x poids (t) x facteur
            $table->decimal('co2_kg', 12, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_legs');
    }
};
