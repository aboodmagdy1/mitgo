<?php

namespace App\Listeners;

use App\Enums\TripRequestOutcome;
use App\Events\TripRequestSent;
use App\Models\TripRequestLog;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogTripRequestToDb implements ShouldQueue
{
    /**
     * Handle the event. Runs async to avoid blocking the request.
     */
    public function handle(TripRequestSent $event): void
    {
        TripRequestLog::firstOrCreate(
            [
                'trip_id' => $event->trip->id,
                'driver_id' => $event->driverId,
            ],
            [
                'outcome' => TripRequestOutcome::PENDING,
                'sent_at' => now(),
            ]
        );
    }
}
