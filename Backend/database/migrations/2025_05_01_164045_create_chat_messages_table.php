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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // المريض الذي أرسل الرسالة
            $table->enum('sender', ['patient', 'bot']); // نوع المُرسل: مريض أو بوت
            $table->text('message'); // الرسالة نفسها
            $table->timestamps(); // تاريخ ووقت الرسالة
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
