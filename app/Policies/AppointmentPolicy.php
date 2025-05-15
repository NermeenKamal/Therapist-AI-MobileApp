<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Appointment;

class AppointmentPolicy
{
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->patient_id || $user->id === $appointment->doctor_id;
    }
}
