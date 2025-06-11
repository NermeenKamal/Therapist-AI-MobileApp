<?php
namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Services\FCMService;

class SendAppointmentBookedNotification
{
    public function handle(AppointmentBooked $event): void
    {
        $appointment = $event->appointment;
        $appointment->load(['doctor', 'patient']); // مهم علشان يمنع eager loading خطأ
        
        $doctor = $appointment->doctor;
        $patient = $appointment->patient;
        
        if ($doctor && $doctor->fcm_token) {
            app(FCMService::class)->sendToUser(
                $doctor->fcm_token,
                'تم حجز موعد جديد',
                'تم الحجز بواسطة: ' . ($patient?->name ?? 'مريض غير معروف'),
                ['appointment_id' => $appointment->id]
            );
        }

    }
}
