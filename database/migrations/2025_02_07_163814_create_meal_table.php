<?php

// needed packages
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
        // schema class create   table meal   // add column and its data type
        Schema::create('meal', function (Blueprint $table) {   // object blueprint
            $table->id();  //auto generation  key unique
            $table->timestamps(); // logs history in database date: creat at column , update at
            $table->string('name');
        });
    }

    /**
     * Reverse the migrations.  rollback data delete from database
     */
    public function down(): void
    {
        Schema::dropIfExists('meal');
    }
};
