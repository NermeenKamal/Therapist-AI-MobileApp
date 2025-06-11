<?php
namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Services\FCMService;

class SendAppointmentBookedNotification
{
    public function handle(AppointmentBooked $event): void
    {
        $appointment = $event->appointment;
        $doctor = $appointment->doctor;

        if ($doctor && $doctor->fcm_token) {
            app(FCMService::class)->sendToUser(
                $doctor->fcm_token,
                'تم حجز موعد جديد',
                'تم الحجز بواسطة: ' . $appointment->patient?->name,
                ['appointment_id' => $appointment->id]
            );
        }
    }
}