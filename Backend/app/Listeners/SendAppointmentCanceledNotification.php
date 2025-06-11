<?php
namespace App\Listeners;

use App\Events\AppointmentCanceled;
use App\Services\FCMService;

class SendAppointmentCanceledNotification
{
    public function handle(AppointmentCanceled $event): void
    {
        $appointment = $event->appointment;
        $appointment->load(['doctor', 'patient']);

        // مثلاً لو الطبيب هو اللي لغى، ابعت للمريض، والعكس
        $receiver = $appointment->patient;

        if ($receiver && $receiver->fcm_token) {
            app(FCMService::class)->sendToUser(
                $receiver->fcm_token,
                'تم إلغاء الموعد',
                'رقم الموعد: ' . $appointment->id,
                ['appointment_id' => $appointment->id]
            );
        }
    }
}
