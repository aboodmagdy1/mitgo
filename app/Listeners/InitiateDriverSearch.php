<?php

namespace App\Listeners;

use App\Events\TripCreated;
use App\Jobs\ProcessDriverSearch;
use App\Services\DriverSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use function App\Helpers\setting;

class InitiateDriverSearch implements ShouldQueue
{
    use InteractsWithQueue;

    protected DriverSearchService $driverSearchService;

    /**
     * Create the event listener.
     */
    public function __construct(DriverSearchService $driverSearchService)
    {
        $this->driverSearchService = $driverSearchService;
    }

    /**
     * Handle the event.
     */
    public function handle(TripCreated $event): void
    {
        Log::info('TripCreated event received, initiating automatic driver search', [
            'trip_id' => $event->trip->id
        ]);

        try {
            // Initialize search tracking
            $tripId = $event->trip->id;
            
            // Idempotency: prevent starting the same search twice
            $startedKey = "trip:{$tripId}:search_started";
            if (Redis::get($startedKey)) {
                Log::warning('Driver search already initiated for this trip, skipping duplicate start', [
                    'trip_id' => $tripId
                ]);
                return;
            }

            // Initialize Redis tracking
            $initialRadius = (float) (setting('general', 'initial_radius_km') ?: 2.0);
            Redis::set("trip:{$tripId}:current_wave", 0);
            Redis::set("trip:{$tripId}:current_radius", $initialRadius);
            Redis::del("trip:{$tripId}:notified_drivers");
            Redis::set($startedKey, 1);

            Log::info('Initialized search tracking', [
                'trip_id' => $tripId,
                'initial_radius' => $initialRadius
            ]);

            // Dispatch automatic wave-based search job (starts from wave 1)
            ProcessDriverSearch::dispatch($tripId, 1);

            Log::info('ProcessDriverSearch job dispatched', [
                'trip_id' => $tripId,
                'starting_wave' => 1
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initiate driver search', [
                'trip_id' => $event->trip->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry if using queues
            throw $e;
        }
    }
}

