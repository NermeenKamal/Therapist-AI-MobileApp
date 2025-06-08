<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'appointment_date'  => $this->appointment_date,
            'status'           => $this->status,
            'notes'            => $this->notes,
            'price'            => $this->price,
            'doctor'           => [
                'id'            => $this->doctor->id,
                'name'          => $this->doctor->name,
                'specialization'=> $this->doctor->specialization,
                'profile_image' => $this->doctor->profile_image,
            ],
            'patient'          => [
                'id'            => $this->patient->id,
                'name'          => $this->patient->name,
                'profile_image' => $this->patient->profile_image,
            ],
            'created_at'       => $this->created_at->toDateTimeString(),
            'updated_at'       => $this->updated_at->toDateTimeString(),
        ];
    }
}

