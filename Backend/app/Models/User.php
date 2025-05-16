<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    use CanResetPasswordTrait;


    protected $fillable = [
        'name',
        'email',
        'password',
        'fcm_token',
        'is_verified_by_ocr',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_verified_by_ocr' => 'boolean',
    ];

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function isDoctor()
    {
        return $this->role === 'doctor';
    }

    public function isPatient()
    {
        return $this->role === 'patient';
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function reports()
    {
        return $this->hasMany(PatientReport::class);
    }
}
