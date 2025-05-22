<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Patient extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'fcm_token',
        'email_verified'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'email_verified' => 'boolean'
    ];

    /**
     * التحقق من أن المريض مُفعل
     */
    public function isVerified(): bool
    {
        return $this->email_verified;
    }

    /**
     * المواعيد الخاصة بالمريض
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * الرسائل المرسلة من المريض
     */
    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id')->where('sender_type', 'patient');
    }

    /**
     * الرسائل المستلمة للمريض
     */
    public function receivedMessages()
    {
        return $this->hasMany(ChatMessage::class, 'receiver_id')->where('receiver_type', 'patient');
    }
}
