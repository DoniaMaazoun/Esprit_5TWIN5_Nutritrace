<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fiche consommateur du lot (rédigée par le producteur ou générée par l'IA),
     * affichée sur la page publique d'origine. Les "notes" restent privées.
     */
    public function up(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->text('public_description')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropColumn('public_description');
        });
    }
};
