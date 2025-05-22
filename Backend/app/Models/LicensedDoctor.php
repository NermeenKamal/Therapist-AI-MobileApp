<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicensedDoctor extends Model
{
    use HasFactory;

    protected $table = 'licensed_doctors';

    protected $fillable = [
        'full_name',
        'license_number',
        'specialization',
        'email',
        'phone_number',
        'national_id',
        'verified'
    ];

    protected $casts = [
        'verified' => 'boolean',
        'registered_at' => 'datetime'
    ];

    /**
     * التحقق من صحة بيانات الطبيب
     */
    public static function validateDoctorData(array $data): ?self
    {
        return self::where('email', $data['email'])
                   ->where('license_number', $data['license_number'])
                   ->where('national_id', $data['national_id'])
                   ->where('specialization', $data['specialization'])
                   ->first();
    }

    /**
     * التحقق من أن الطبيب مُفعل
     */
    public function isVerified(): bool
    {
        return $this->verified === true;
    }

    /**
     * الحصول على الأطباء المُفعلين فقط
     */
    public static function getVerifiedDoctors()
    {
        return self::where('verified', true)->get();
    }

    /**
     * الحصول على الأطباء في انتظار التفعيل
     */
    public static function getPendingDoctors()
    {
        return self::where('verified', false)->get();
    }
}
