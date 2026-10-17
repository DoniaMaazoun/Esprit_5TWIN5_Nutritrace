<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 3 - Modes de transport et leurs facteurs d'émission (kg CO2e / tonne.km)
     */
    public function up(): void
    {
        Schema::create('transport_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Code technique : truck_heavy, truck_light, van, rail, river, sea, air
            $table->string('code')->unique();
            // Facteur d'émission en kg CO2e par tonne.km (source ADEME)
            $table->decimal('emission_factor', 10, 5);
            $table->string('source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_modes');
    }
};
