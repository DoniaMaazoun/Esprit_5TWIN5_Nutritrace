<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exploitations agricoles (fermes) appartenant à un producteur.
     */
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('governorate', 50);
            $table->string('city', 80);
            $table->string('address', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('total_area', 10, 2); // en hectares
            $table->string('farming_type', 30);
            $table->string('irrigation_type', 30);
            $table->string('soil_type', 30)->nullable();
            $table->string('main_crops', 255)->nullable();
            $table->string('certification', 100)->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['governorate', 'farming_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
