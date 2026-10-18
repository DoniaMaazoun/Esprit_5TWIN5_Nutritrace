<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 3 - Coordonnées du point d'arrivée de chaque étape (carte Leaflet) et suivi des
     * étapes créées à partir de la recommandation IA (CO2 économisé du dashboard)
     */
    public function up(): void
    {
        Schema::table('shipment_legs', function (Blueprint $table) {
            $table->decimal('to_latitude', 10, 7)->nullable()->after('to_location');
            $table->decimal('to_longitude', 10, 7)->nullable()->after('to_latitude');
            $table->boolean('from_recommendation')->default(false)->after('co2_kg');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_legs', function (Blueprint $table) {
            $table->dropColumn(['to_latitude', 'to_longitude', 'from_recommendation']);
        });
    }
};
