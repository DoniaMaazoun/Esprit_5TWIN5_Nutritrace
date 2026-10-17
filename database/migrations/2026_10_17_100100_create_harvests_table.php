<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Récoltes : origine traçable d'un lot produit sur une exploitation.
     */
    public function up(): void
    {
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('batch_code', 30)->unique();
            $table->string('product_name', 100);
            $table->string('category', 30);
            $table->string('variety', 100)->nullable();
            $table->string('plot_name', 100)->nullable();
            $table->decimal('plot_area', 10, 2)->nullable(); // en hectares
            $table->date('planting_date')->nullable();
            $table->date('harvest_date');
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 20);
            $table->char('quality_grade', 1);
            $table->text('treatments')->nullable();
            $table->string('storage_conditions', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('in_stock');
            $table->timestamps();

            $table->index(['farm_id', 'harvest_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
