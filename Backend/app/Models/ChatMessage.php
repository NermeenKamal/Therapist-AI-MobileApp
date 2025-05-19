<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'sender_type',
        'sender_id',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the appointment associated with this message
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the sender (polymorphic relationship)
     */
    public function sender()
    {
        return $this->sender_type === 'doctor'
            ? $this->belongsTo(Doctor::class, 'sender_id')
            : $this->belongsTo(Patient::class, 'sender_id');
    }
}
