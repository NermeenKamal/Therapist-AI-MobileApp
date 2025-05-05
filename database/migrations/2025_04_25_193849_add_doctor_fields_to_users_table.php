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

            // تحديد دور المستخدم (مريض أو دكتور)
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('patient')->after('email');
            }

            // الرقم القومي للدكتور (إجباري ومميز)
            if (!Schema::hasColumn('users', 'national_id')) {
                $table->string('national_id')->unique()->after('password');
            }

            // التخصص الطبي (اختياري)
            if (!Schema::hasColumn('users', 'specialization')) {
                $table->string('specialization')->nullable()->after('national_id');
            }

            // صورة بطاقة الرقم القومي
            if (!Schema::hasColumn('users', 'id_card_path')) {
                $table->string('id_card_path')->nullable()->after('specialization');
            }

            // صورة شخصية للدكتور
            if (!Schema::hasColumn('users', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('id_card_path');
            }

            // رقم الهاتف
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('profile_image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [
                'role',
                'national_id',
                'specialization',
                'id_card_path',
                'profile_image',
                'phone_number',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
