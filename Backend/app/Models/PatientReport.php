<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientReport extends Model
{
    protected $fillable = [
        'patient_id',
        'file_path',
        'original_filename',
    ];
}
