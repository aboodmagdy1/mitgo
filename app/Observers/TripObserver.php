<?php

namespace App\Observers;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Services\CashbackService;
use Illuminate\Support\Facades\Log;

class TripObserver
{
    public function updated(Trip $trip): void
    {
        if (!$trip->wasChanged('status')) {
            return;
        }

        if ($trip->status !== TripStatus::COMPLETED) {
            return;
        }

        if ($trip->cashbackUsage()->exists()) {
            return;
        }

        if ($trip->actual_fare <= 0) {
            return;
        }

        try {
            app(CashbackService::class)->awardForTrip($trip, (float) $trip->actual_fare);
        } catch (\Throwable $e) {
            Log::error('Failed to award cashback in TripObserver', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

