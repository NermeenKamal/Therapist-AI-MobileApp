<?php

// database/migrations/2025_05_10_000000_add_missing_columns_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add new columns (only if they don't exist)
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['patient', 'doctor'])->default('patient');
            }
            if (!Schema::hasColumn('users', 'national_id')) {
                $table->string('national_id', 14)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'is_verified_by_ocr')) {
                $table->boolean('is_verified_by_ocr')->default(false);
            }
            if (!Schema::hasColumn('users', 'ocr_debug_text')) {
                $table->text('ocr_debug_text')->nullable();
            }
            if (!Schema::hasColumn('users', 'sentiment_score')) {
                $table->float('sentiment_score')->nullable();
            }
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable();
            }

            // Drop obsolete columns (only if they exist)
            $columnsToDrop = [
                'specialization',
                'id_card_path',
                'report_file_path',
                'device_token',
                'profile_image',
                'profile_picture'
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverse: Recreate dropped columns
            $table->string('specialization')->nullable();
            $table->string('id_card_path')->nullable();
            $table->string('report_file_path')->nullable();
            $table->string('device_token')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('profile_picture')->nullable();

            // Reverse: Remove new columns
            $table->dropColumn([
                'role',
                'national_id',
                'is_verified_by_ocr',
                'ocr_debug_text',
                'sentiment_score',
                'fcm_token'
            ]);
        });
    }
}
