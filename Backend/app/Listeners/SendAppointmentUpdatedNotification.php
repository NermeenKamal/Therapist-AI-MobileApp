<?php
namespace App\Listeners;

use App\Events\AppointmentUpdated;
use App\Services\FCMService;

class SendAppointmentUpdatedNotification
{
    public function handle(AppointmentUpdated $event): void
    {
        $appointment = $event->appointment;
        $appointment->load(['doctor', 'patient']);

        // ابعت للطرفين: الدكتور والمريض
        foreach ([$appointment->doctor, $appointment->patient] as $user) {
            if ($user && $user->fcm_token) {
                app(FCMService::class)->sendToUser(
                    $user->fcm_token,
                    'تم تعديل الموعد',
                    'تم تعديل موعد رقم: ' . $appointment->id,
                    ['appointment_id' => $appointment->id]
                );
            }
        }
    }
}

