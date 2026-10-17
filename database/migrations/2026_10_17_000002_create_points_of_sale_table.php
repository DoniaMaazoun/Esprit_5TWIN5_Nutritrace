<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 3 - Points de vente et entrepôts du distributeur
     */
    public function up(): void
    {
        Schema::create('points_of_sale', function (Blueprint $table) {
            $table->id();
            // Distributeur propriétaire (users.pro_type = distributor)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Type : store, warehouse, market
            $table->string('type')->default('store');
            $table->string('address');
            $table->string('city');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_of_sale');
    }
};
