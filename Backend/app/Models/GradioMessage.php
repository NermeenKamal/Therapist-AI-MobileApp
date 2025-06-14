<?php

// app/Models/GradioMessage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradioMessage extends Model
{
    protected $fillable = ['message', 'conversation_id', 'response', 'status'];
}
