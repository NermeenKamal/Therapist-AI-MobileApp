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
}
