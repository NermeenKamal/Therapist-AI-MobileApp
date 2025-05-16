<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $table = 'doctors';

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_number',
        'national_id',
        'national_id_path',
        'specialization',
        'bio',
        'session_price',
        'medical_license_path',
        'profile_image',
        'fcm_token',
        'is_verified',
        'is_verified_by_ocr'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'session_price' => 'decimal:2',
        'is_verified' => 'boolean',
        'is_verified_by_ocr' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }
} 
