<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 3 - Une expédition transporte désormais un lot de production du transformateur
     * (module 2, table production_lots) et non plus une récolte brute du producteur.
     * Chaîne respectée : Producteur → Transformateur → Distributeur.
     */
    public function up(): void
    {
        if (Schema::hasColumn('shipments', 'harvest_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('harvest_id');
            });
        }

        // Peut déjà exister (migration 2026_10_22_212246 du module panier / NutriAI)
        if (! Schema::hasColumn('shipments', 'production_lot_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                // Si le lot est supprimé, l'expédition reste mais n'est plus reliée
                $table->foreignId('production_lot_id')->nullable()->after('user_id')
                    ->constrained('production_lots')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_lot_id');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('harvest_id')->nullable()->after('user_id')
                ->constrained('harvests')->nullOnDelete();
        });
    }
};
