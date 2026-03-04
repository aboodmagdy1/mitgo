<?php

namespace App\Jobs;

use App\Events\TripRequestExpired;
use App\Models\Trip;
use App\Enums\TripStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ExpireTripRequest implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tripId;

    /**
     * The unique ID of the job (prevents duplicate jobs for same trip).
     */
    public function uniqueId(): string
    {
        return "expire-trip-{$this->tripId}";
    }

    /**
     * Create a new job instance.
     */
    public function __construct(int $tripId)
    {
        $this->tripId = $tripId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ExpireTripRequest job running', ['trip_id' => $this->tripId]);

        // Check if trip was already resolved (accept/cancel/no-driver)
        if (Redis::get("trip:{$this->tripId}:resolved")) {
            Log::info('Trip already resolved, job exiting', ['trip_id' => $this->tripId]);
            return;
        }

        // Get the expiration timestamp
        $expiresAt = Redis::get("trip:{$this->tripId}:expires_at");

        if (!$expiresAt) {
            Log::info('No expiration timestamp found, trip likely completed', ['trip_id' => $this->tripId]);
            return;
        }

        $now = now()->timestamp;

        // If acceptance window is still open (another wave extended it), reschedule
        if ($now < $expiresAt) {
            $remainingSeconds = $expiresAt - $now;

            Log::info('Trip acceptance window still open, rescheduling job', [
                'trip_id' => $this->tripId,
                'remaining_seconds' => $remainingSeconds,
                'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            ]);

            $this->release($remainingSeconds);
            return;
        }

        // Window has closed, check if trip is still searching
        $trip = Trip::find($this->tripId);

        if (!$trip || $trip->status !== TripStatus::SEARCHING) {
            Log::info('Trip no longer searching or not found', [
                'trip_id' => $this->tripId,
                'status' => $trip ? $trip->status->value : 'not found'
            ]);
            return;
        }

        // Broadcast expiration to shared channel (all drivers will receive)
        event(new TripRequestExpired($this->tripId));

        // Clear all driver active requests
        $notifiedDrivers = Redis::smembers("trip:{$this->tripId}:notified_drivers") ?: [];
        foreach ($notifiedDrivers as $driverId) {
            Redis::del("driver:{$driverId}:active_request");
        }

        Log::info('Trip request expired - broadcast complete', [
            'trip_id' => $this->tripId,
            'drivers_cleared' => count($notifiedDrivers),
        ]);
    }
}

