<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recettes: Formules de transformation créées par les Transformateurs
     */
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processor_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('yield_quantity', 10, 2); // Quantité produite
            $table->string('yield_unit', 20); // kg, L, unités, etc.
            $table->integer('preparation_time_minutes')->default(0); // Durée de préparation
            $table->text('instructions')->nullable(); // Mode préparation
            $table->boolean('is_active')->default(true); // Recette active/inactive
            $table->timestamps();

            $table->index(['processor_id']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
