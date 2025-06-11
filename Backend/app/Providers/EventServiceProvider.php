<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// Events
use App\Events\AppointmentBooked;
use App\Events\AppointmentCanceled;
use App\Events\AppointmentConfirmed;
use App\Events\AppointmentUpdated;

// Listeners
use App\Listeners\SendAppointmentBookedNotification;
use App\Listeners\SendAppointmentCanceledNotification;
use App\Listeners\SendAppointmentConfirmedNotification;
use App\Listeners\SendAppointmentUpdatedNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AppointmentBooked::class => [
            SendAppointmentBookedNotification::class,
        ],
        AppointmentCanceled::class => [
            SendAppointmentCanceledNotification::class,
        ],
        AppointmentConfirmed::class => [
            SendAppointmentConfirmedNotification::class,
        ],
        AppointmentUpdated::class => [
            SendAppointmentUpdatedNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
