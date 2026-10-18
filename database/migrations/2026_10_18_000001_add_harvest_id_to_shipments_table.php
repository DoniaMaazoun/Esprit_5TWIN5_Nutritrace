<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 3 - Relie chaque expédition à un lot du module 1 (table harvests)
     */
    public function up(): void
    {
        // Ancienne colonne provisoire, avant la fusion du module 1
        if (Schema::hasColumn('shipments', 'batch_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropIndex(['batch_id']);
                $table->dropColumn('batch_id');
            });
        }

        Schema::table('shipments', function (Blueprint $table) {
            // Si le lot est supprimé, l'expédition reste mais n'est plus reliée
            $table->foreignId('harvest_id')->nullable()->after('user_id')
                ->constrained('harvests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('harvest_id');
        });
    }
};
