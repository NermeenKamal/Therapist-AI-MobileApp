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
        Schema::table('doctors', function (Blueprint $table) {
            // Make session_price nullable
            $table->decimal('session_price', 8, 2)->nullable()->change();
            
            // Add national_id_path column
            $table->string('national_id_path')->nullable()->after('national_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            // Revert session_price to non-nullable
            $table->decimal('session_price', 8, 2)->nullable(false)->change();
            
            // Remove national_id_path column
            $table->dropColumn('national_id_path');
        });
    }
}; 