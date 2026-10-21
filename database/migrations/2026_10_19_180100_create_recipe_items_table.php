<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ingrédients des recettes (composants nécessaires pour chaque recette)
     */
    public function up(): void
    {
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->string('raw_material_name', 150); // Ex: "Tomates Bio", "Pommes de terre Charlotte"
            $table->decimal('quantity_required', 10, 2); // Quantité requise
            $table->string('unit', 20); // kg, L, unités, etc.
            $table->text('notes')->nullable(); // Notes supplémentaires
            $table->timestamps();

            $table->index(['recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
    }
};
