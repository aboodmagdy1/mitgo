<?php

namespace App\Jobs;

use App\Models\Trip;
use App\Enums\TripStatus;
use App\Events\SearchProgress;
use App\Services\DriverSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use function App\Helpers\setting;

class ProcessDriverSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tripId;
    public int $currentWave;

    /**
     * Create a new job instance.
     */
    public function __construct(int $tripId, int $currentWave)
    {
        $this->tripId = $tripId;
        $this->currentWave = $currentWave;
    }

    /**
     * Execute the job.
     */
    public function handle(DriverSearchService $searchService): void
    {
        $trip = Trip::find($this->tripId);

        // Check if trip still searching (stops waves if driver accepted/cancelled)
        if (!$trip || $trip->status !== TripStatus::SEARCHING) {
            Log::info('ProcessDriverSearch exiting - trip no longer searching', [
                'trip_id' => $this->tripId,
                'wave' => $this->currentWave,
                'status' => $trip ? $trip->status->value : 'not found'
            ]);
            return;
        }

        $maxWaves = (int) (setting('general', 'search_wave_count') ?: 10);
        $waveTime = (int) (setting('general', 'search_wave_time') ?: 30);

        // Log::info('Processing driver search wave', [
        //     'trip_id' => $this->tripId,
        //     'wave' => $this->currentWave,
        //     'max_waves' => $maxWaves,
        //     'wave_time' => $waveTime
        // ]);

        // Execute current wave
        $result = $searchService->searchWave($trip, $this->currentWave);

        // Broadcast progress to client
        // event(new SearchProgress(
        //     $trip->id,
        //     $this->currentWave,
        //     $maxWaves,
        //     $result['drivers_notified']
        // ));

        // Log::info('Search wave completed', [
        //     'trip_id' => $this->tripId,
        //     'wave' => $this->currentWave,
        //     'drivers_notified' => $result['drivers_notified']
        // ]);

        // Check if this is the last wave
        if ($this->currentWave >= $maxWaves) {
            Log::info('Last wave reached, checking trip status', [
                'trip_id' => $this->tripId,
                'wave' => $this->currentWave
            ]);

            // Reload trip to check current status
            $trip->refresh();

            // Only call handleNoDriverFound if trip is STILL in SEARCHING status
            // (if driver accepted during waves, status would have changed)
            if ($trip->status === TripStatus::SEARCHING) {
                Log::info('Trip still searching after all waves, handling no driver found', [
                    'trip_id' => $this->tripId
                ]);
                $searchService->handleNoDriverFound($trip);
            } else {
                Log::info('Trip status changed during waves, not calling handleNoDriverFound', [
                    'trip_id' => $this->tripId,
                    'status' => $trip->status->value
                ]);
            }
            return;
        }

        // Schedule next wave with delay
        ProcessDriverSearch::dispatch($this->tripId, $this->currentWave + 1)
            ->delay(now()->addSeconds($waveTime));

        Log::info('Next wave scheduled', [
            'trip_id' => $this->tripId,
            'next_wave' => $this->currentWave + 1,
            'delay_seconds' => $waveTime
        ]);
    }
}

