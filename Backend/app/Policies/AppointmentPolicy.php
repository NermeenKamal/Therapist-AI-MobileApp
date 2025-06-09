<?php
namespace App\Policies;

use App\Models\Appointment;
use App\Models\Doctor;

class AppointmentPolicy
{
    /** الدكتور فقط يقدر ينشئ موعد متاح */
    public function create($user): bool
    {
        return $user instanceof Doctor; 
    }

    public function cancel($user, Appointment $appointment): bool
    {
        return $user->id === $appointment->patient_id
            || $user->id === $appointment->doctor_id;
    }

    public function update($user, Appointment $appointment): bool
    {
        return $user->id === $appointment->doctor_id;
    }

    public function confirm($user, Appointment $appointment): bool
    {
        return $user->id === $appointment->doctor_id;
    }
}
