<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRating extends Model
{
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'chat_text',
        'sentiment_score',
        'sentiment_label',
    ];

    protected $casts = [
        'sentiment_score' => 'float',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
