<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Appointment;

class AppointmentPolicy
{
    // يسمح للدكتور فقط بإنشاء موعد متاح
    public function create(User $user): bool
    {
        return $user->role === 'doctor';
    }

    // يسمح للمريض أو الدكتور بإلغاء الموعد
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->patient_id || $user->id === $appointment->doctor_id;
    }

    // مثال تحديث الموعد ( يسمح للدكتور فقط بتحديث الموعد )
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->doctor_id;
    }
}
