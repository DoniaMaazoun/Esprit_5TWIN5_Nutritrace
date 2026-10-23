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
        // La colonne peut déjà exister (ajoutée par la migration du module 3 du 2026_10_23)
        if (Schema::hasColumn('shipments', 'production_lot_id')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            // Nullable : une expédition sans lot de production reste valide
            $table->foreignId('production_lot_id')->nullable()->after('user_id')
                ->constrained('production_lots')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('shipments', 'production_lot_id')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_lot_id');
        });
    }
};
