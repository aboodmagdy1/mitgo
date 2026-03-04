<?php

namespace App\Listeners;

use App\Events\TripRequestSent;
use App\Models\Driver;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNewTripRequestFcmNotification implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(TripRequestSent $event): void
    {
        $driver = Driver::with('user')->find($event->driverId);
        if (!$driver?->user) {
            return;
        }

        $this->notificationService->sendNewTripRequestNotification(
            $event->trip->fresh(),
            $driver->user
        );
    }
}
