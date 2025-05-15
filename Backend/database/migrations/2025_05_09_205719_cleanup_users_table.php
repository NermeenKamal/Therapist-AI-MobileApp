<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CleanupUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove obsolete column (if exists)
            if (Schema::hasColumn('users', 'national_id_extracted')) {
                $table->dropColumn('national_id_extracted');
            }

            // Add/modify columns to match requirements (if not exists)
            if (!Schema::hasColumn('users', 'national_id')) {
                $table->string('national_id', 14)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'is_verified_by_ocr')) {
                $table->boolean('is_verified_by_ocr')->default(false)->after('national_id');
            }
            if (!Schema::hasColumn('users', 'ocr_debug_text')) {
                $table->text('ocr_debug_text')->nullable()->after('is_verified_by_ocr');
            }
            if (!Schema::hasColumn('users', 'sentiment_score')) {
                $table->float('sentiment_score')->nullable()->after('ocr_debug_text');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Re-add dropped column
            $table->string('national_id_extracted')->nullable();

            // Remove added columns
            $columns = [
                'national_id',
                'is_verified_by_ocr',
                'ocr_debug_text',
                'sentiment_score'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
