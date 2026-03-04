<?php

namespace App\Listeners;

use App\Events\TripCancelled;
use App\Events\TripCompleted;
use App\Events\TripDriverAccepted;
use App\Events\TripDriverArrived;
use App\Events\TripEnded;
use App\Events\TripNoDriverFound;
use App\Events\TripNoShow;
use App\Events\TripStarted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTripStatusFcmNotification implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handleTripDriverAccepted(TripDriverAccepted $event): void
    {
        $trip = $event->trip->fresh();
        $this->notificationService->sendTripStatusNotification($trip, $trip->status, 'driver');
    }

    public function handleTripDriverArrived(TripDriverArrived $event): void
    {
        $trip = $event->trip->fresh();
        $this->notificationService->sendTripStatusNotification($trip, $trip->status, 'driver');
    }

    public function handleTripStarted(TripStarted $event): void
    {
        $trip = $event->trip->fresh();
        $this->notificationService->sendTripStatusNotification($trip, $trip->status, 'driver');
    }

    public function handleTripEnded(TripEnded $event): void
    {
        $trip = $event->trip->fresh();
        $this->notificationService->sendTripStatusNotification($trip, $trip->status, 'driver');
    }

    public function handleTripCompleted(TripCompleted $event): void
    {
        $trip = $event->trip->fresh();
        $this->notificationService->sendTripStatusNotification($trip, $trip->status, 'driver');
    }

    public function handleTripCancelled(TripCancelled $event): void
    {
        $trip = $event->trip->fresh();
        $this->notificationService->sendTripStatusNotification($trip, $trip->status, $event->cancelledBy);
    }

    public function handleTripNoShow(TripNoShow $event): void
    {
        $trip = $event->trip->fresh();
        $this->notificationService->sendTripStatusNotification($trip, $trip->status, 'driver');
    }

    public function handleTripNoDriverFound(TripNoDriverFound $event): void
    {
        $trip = $event->trip->fresh();
        $this->notificationService->sendTripStatusNotification($trip, $trip->status, 'system');
    }
}
