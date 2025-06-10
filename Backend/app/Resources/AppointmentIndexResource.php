<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AppointmentIndexResource extends JsonResource
{
    public function toArray($request)
    {
        $currentUserId = Auth::id();

        $isDoctor = $this->doctor_id === $currentUserId;

        return [
            'id'               => $this->id,
            'appointment_date' => $this->appointment_date,
            'status'           => $this->status,
            'notes'            => $this->notes,
            'price'            => $this->price,

            // لو المستخدم دكتور، نعرض بيانات المريض
            // لو مريض، نعرض بيانات الدكتور
            $isDoctor ? 'patient' : 'doctor' => [
                'id'             => $isDoctor ? $this->patient?->id : $this->doctor?->id,
                'name'           => $isDoctor ? $this->patient?->name : $this->doctor?->name,
                'specialization' => $isDoctor ? null : $this->doctor?->specialization,
            ],
        ];
    }
}


