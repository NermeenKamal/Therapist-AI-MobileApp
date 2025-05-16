<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'email',
        'password',
        'mobile_number',
        'national_id',
        'id_card_image_path',
        'is_verified_by_ocr',
        'fcm_token',
    ];

    protected $casts = [
        'is_verified_by_ocr' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
} 