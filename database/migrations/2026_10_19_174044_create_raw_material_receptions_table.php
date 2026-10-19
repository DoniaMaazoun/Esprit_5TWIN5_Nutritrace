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
        Schema::create('raw_material_receptions', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to harvest (from producteur)
            $table->foreignId('harvest_id')->constrained()->onDelete('cascade');
            
            // Foreign key to processor user (transformateur)
            $table->foreignId('processor_id')->constrained('users')->onDelete('cascade');
            
            // Reception details
            $table->decimal('quantity_received', 10, 2); // kg received
            $table->string('unit', 50)->default('kg'); // unit of measure
            $table->datetime('reception_date'); // when received
            $table->enum('status', ['pending', 'received', 'rejected'])->default('pending');
            
            // Quality check
            $table->text('quality_notes')->nullable(); // notes on quality
            $table->enum('quality_status', ['good', 'acceptable', 'damaged'])->default('good');
            
            // Storage info
            $table->string('storage_location', 255)->nullable(); // where stored in warehouse
            $table->decimal('temperature', 5, 2)->nullable(); // storage temperature
            
            // Rejection info
            $table->text('rejection_reason')->nullable(); // why rejected
            $table->datetime('rejection_date')->nullable();
            
            // Audit trail
            $table->text('internal_notes')->nullable(); // internal remarks
            $table->timestamp('verified_at')->nullable(); // when verified by processor
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_receptions');
    }
};
