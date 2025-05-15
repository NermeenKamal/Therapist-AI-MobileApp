<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAiAndOcrFields extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'national_id_extracted')) {
                $table->string('national_id_extracted')->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'is_verified_by_ocr')) {
                $table->boolean('is_verified_by_ocr')->default(false)->after('national_id_extracted');
            }

            if (!Schema::hasColumn('users', 'ocr_debug_text')) {
                $table->text('ocr_debug_text')->nullable()->after('is_verified_by_ocr');
            }
        });
    }


        public function down(): void
    {
        Schema::table('chat_ratings', function (Blueprint $table) {
            $table->dropColumn(['sentiment_score', 'sentiment_label']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['canceled_by']);
            $table->dropColumn(['status', 'canceled_by']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['national_id_extracted', 'is_verified_by_ocr', 'ocr_debug_text']);
        });
    }
}
