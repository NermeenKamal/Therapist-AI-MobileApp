<?php
namespace App\Listeners;

use App\Events\AppointmentConfirmed;
use App\Services\FCMService;

class SendAppointmentConfirmedNotification
{
    public function handle(AppointmentConfirmed $event): void
    {
        $appointment = $event->appointment;
        $appointment->load('patient');

        $patient = $appointment->patient;

        if ($patient && $patient->fcm_token) {
            app(FCMService::class)->sendToUser(
                $patient->fcm_token,
                'تم تأكيد الموعد',
                'قام الدكتور بتأكيد الموعد رقم: ' . $appointment->id,
                ['appointment_id' => $appointment->id]
            );
        }
    }
}

