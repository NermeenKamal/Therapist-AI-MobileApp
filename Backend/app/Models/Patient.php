<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Patient extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'patients';

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_number',
        'national_id',
        'date_of_birth',
        'gender',
        'medical_history',
        'current_medications',
        'allergies',
        'emergency_contact_name',
        'emergency_contact_number',
        'profile_image',
        'fcm_token'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'email_verified_at' => 'datetime',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }
} 
