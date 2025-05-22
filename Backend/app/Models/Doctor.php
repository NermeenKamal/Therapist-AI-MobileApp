<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Doctor extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_number',
        'national_id',
        'license_number',
        'specialization',
        'medical_license_path',
        'bio',
        'session_price',
        'profile_image',
        'fcm_token',
        'clinic_address',
        'email_verified',
        'is_verified_by_ocr',
        'license_number'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'email_verified' => 'boolean',
        'is_verified_by_ocr' => 'boolean',
        'session_price' => 'decimal:2'
    ];

    /**
     * التحقق من أن الطبيب مُفعل بالكامل
     */
    public function isFullyVerified(): bool
    {
        return $this->email_verified && $this->isLicenseVerified();
    }

    /**
     * التحقق من صحة الترخيص
     */
    public function isLicenseVerified(): bool
    {
        $licensedDoctor = LicensedDoctor::where('email', $this->email)
                                       ->where('license_number', $this->license_number)
                                       ->where('verified', true)
                                       ->first();
        
        return $licensedDoctor !== null;
    }

    /**
     * الحصول على بيانات الترخيص
     */
    public function licensedDoctor()
    {
        return $this->hasOne(LicensedDoctor::class, 'email', 'email');
    }

    /**
     * المواعيد الخاصة بالطبيب
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * جدولة الطبيب
     */
    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    /**
     * المقالات التي كتبها الطبيب
     */
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
