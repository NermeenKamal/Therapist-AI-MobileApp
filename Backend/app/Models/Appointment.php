<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'doctor_id', 'date', 'time'
    ];

    protected $dates = ['date'];

    public final function patient() : BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public final function doctor() : BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
