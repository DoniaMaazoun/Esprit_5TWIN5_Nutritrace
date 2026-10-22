<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade');
            $table->foreignId('processor_id')->constrained('users')->onDelete('cascade');
            $table->decimal('quantity_produced', 10, 2);
            $table->string('unit')->default('kg'); // kg, g, L, mL, unités, etc.
            $table->date('production_date');
            $table->date('expiry_date');
            $table->enum('status', ['produced', 'packaged', 'shipped', 'archived'])->default('produced');
            $table->string('qr_code_data')->nullable()->unique(); // QR code content/unique identifier
            $table->text('production_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_lots');
    }
};
