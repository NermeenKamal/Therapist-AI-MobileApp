<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens; // Add this import

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens; // Add HasApiTokens trait here

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Make sure 'role' is included in fillable
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected final function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public final function appointmentsAsPatient(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public final function appointmentsAsDoctor(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }
}
