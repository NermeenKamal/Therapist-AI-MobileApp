<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    protected $fillable = ['notifiable_type', 'notifiable_id', 'title', 'message', 'is_read'];

    protected $hidden = ['notifiable_type']; 

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
    public function sender()
{
    return $this->morphTo();
}

}

