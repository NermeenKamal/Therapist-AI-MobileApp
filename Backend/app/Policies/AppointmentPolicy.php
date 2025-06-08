<?php
namespace App\Policies;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\Appointment;

class AppointmentPolicy
{
    // دكتور فقط يقدر ينشئ مواعيد متاحة
    public function create($user): bool
{
    \Log::info('Policy create called with:', [
        'class' => get_class($user),
        'attributes' => $user?->toArray(),
    ]);

    return $user instanceof \App\Models\Doctor;
}

    
    // المريض أو الدكتور يقدر يلغي
    public function cancel(Authenticatable $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->patient_id
            || $user->id === $appointment->doctor_id;
    }
    
    // الدكتور فقط يقدر يعدل المواعيد
    public function update(Authenticatable $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->doctor_id;
    }
    
    public function confirm(Authenticatable $user, Appointment $appointment): bool
    {
        // فقط الدكتور صاحب الموعد يقدر يؤكد
        return $user->id === $appointment->doctor_id;
    }
}
