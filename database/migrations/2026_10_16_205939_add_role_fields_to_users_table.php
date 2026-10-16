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
        Schema::table('users', function (Blueprint $table) {
            // Role: admin, pro, consumer
            $table->string('role')->default('consumer')->after('email');
            
            // Professional type: producer, processor, distributor
            $table->string('pro_type')->nullable()->after('role');
            
            // Company/Farm name
            $table->string('company_name')->nullable()->after('pro_type');
            
            // Tax ID / Registration number
            $table->string('tax_id')->nullable()->after('company_name');
            
            // Account status: active, pending, approved, rejected
            $table->string('status')->default('active')->after('tax_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'pro_type', 'company_name', 'tax_id', 'status']);
        });
    }
};
