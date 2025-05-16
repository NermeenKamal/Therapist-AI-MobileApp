<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'device_token',
        'platform',
    ];
}
