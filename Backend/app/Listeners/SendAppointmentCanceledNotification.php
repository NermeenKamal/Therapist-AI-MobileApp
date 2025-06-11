<?php
namespace App\Listeners;

use App\Events\AppointmentCanceled;
use App\Services\FCMService;

class SendAppointmentCanceledNotification
{
    public function handle(AppointmentCanceled $event): void
    {
        $appointment = $event->appointment;
        $other = $appointment->patient_id === auth()->id()
            ? $appointment->doctor
            : $appointment->patient;

        if ($other && $other->fcm_token) {
            app(FCMService::class)->sendToUser(
                $other->fcm_token,
                'تم إلغاء الموعد',
                'رقم الموعد: ' . $appointment->id,
                ['appointment_id' => $appointment->id]
            );
        }
    }
}