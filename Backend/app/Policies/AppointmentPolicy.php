<?php
namespace App\Policies;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\Appointment;

class AppointmentPolicy
{
    // دكتور فقط يقدر ينشئ مواعيد متاحة
    public function create(Authenticatable $user): bool
    {
        return $user->user_type === 'doctor'; // غيّر من role إلى user_type
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
