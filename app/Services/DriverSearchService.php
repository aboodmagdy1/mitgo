<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\Driver;
use App\Enums\TripStatus;
use App\Events\TripNoDriverFound;
use App\Events\TripRequestSent;
use App\Events\TripRequestExpired;
use App\Jobs\ExpireTripRequest;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function App\Helpers\setting;

class DriverSearchService
{
    private function getInitialRadiusKm(): float
    {
        return (float) (setting('general', 'initial_radius_km') ?: 2.0);
    }

    private function getRadiusIncrementKm(): float
    {
        return (float) (setting('general', 'radius_increment_km') ?: 1.0);
    }

    private function getDriversPerWave(): int
    {
        return (int) (setting('general', 'drivers_per_wave') ?: 5);
    }

    /**
     * Initiate the first wave of driver search for a trip
     */
    public function initiateSearch(Trip $trip): void
    {
        Log::info('Initiating driver search', ['trip_id' => $trip->id]);

        // Idempotency: prevent starting the same search twice
        $startedKey = "trip:{$trip->id}:search_started";
        if (Redis::get($startedKey)) {
            Log::warning('Driver search already initiated for this trip, skipping duplicate start', [
                'trip_id' => $trip->id
            ]);
            return;
        }

        // Initialize Redis tracking
        $this->initializeSearchTracking($trip->id);
        Redis::set($startedKey, 1);

        // Execute first wave search
        $this->searchNextWave($trip);
    }

    /**
     * Search for drivers in the next wave (manual retry from client)
     */
    public function searchNextWave(Trip $trip): array
    {
        // Get current wave
        $currentWave = (int) Redis::get("trip:{$trip->id}:current_wave") ?: 0;
        $nextWave = $currentWave + 1;

        Log::info('Searching wave', ['trip_id' => $trip->id, 'wave' => $nextWave]);

        // Check if max waves reached
        $maxWaves = setting('general', 'search_wave_count') ?: 10;
        if ($nextWave > $maxWaves) {
            Log::warning('Max waves reached', ['trip_id' => $trip->id, 'waves' => $nextWave]);
            $this->handleNoDriverFound($trip);
            return [
                'success' => false,
                'message' => __('No drivers available. Maximum search attempts reached.'),
                'wave' => $nextWave - 1,
            ];
        }

        // Verify trip is still searching
        $trip->refresh();
        if ($trip->status !== TripStatus::SEARCHING) {
            Log::info('Trip no longer searching', ['trip_id' => $trip->id, 'status' => $trip->status->value]);
            return [
                'success' => false,
                'message' => __('Trip is no longer searching for drivers.'),
            ];
        }

        // Get current radius
        $currentRadius = (float) Redis::get("trip:{$trip->id}:current_radius") ?: $this->getInitialRadiusKm();

        // Get already notified drivers
        $notifiedDriverIds = Redis::smembers("trip:{$trip->id}:notified_drivers") ?: [];

        // Find available drivers
        $availableDrivers = $this->getAvailableDrivers(
            (float) $trip->pickup_lat,
            (float) $trip->pickup_long,
            $currentRadius,
            $trip->vehicle_type_id,
            $notifiedDriverIds
        );

        Log::info('Available drivers found', [
            'trip_id' => $trip->id,
            'count' => count($availableDrivers),
            'radius' => $currentRadius
        ]);

        // Expand radius ONLY if there are zero available drivers in the current radius
        if (count($availableDrivers) === 0) {
            // Expand radius and search again
            $expandedRadius = $currentRadius + $this->getRadiusIncrementKm();
            Log::info('Expanding search radius', [
                'trip_id' => $trip->id,
                'old_radius' => $currentRadius,
                'new_radius' => $expandedRadius
            ]);

            $availableDrivers = $this->getAvailableDrivers(
                (float) $trip->pickup_lat,
                (float) $trip->pickup_long,
                $expandedRadius,
                $trip->vehicle_type_id,
                $notifiedDriverIds
            );

            // Update radius in Redis
            Redis::set("trip:{$trip->id}:current_radius", $expandedRadius);
            $currentRadius = $expandedRadius;
        }

        // If still no drivers found
        if (empty($availableDrivers)) {
            Log::warning('No drivers available in radius', [
                'trip_id' => $trip->id,
                'radius' => $currentRadius
            ]);

            // If this was the last wave, mark as no driver found
            if ($nextWave >= $maxWaves) {
                $this->handleNoDriverFound($trip);
                return [
                    'success' => false,
                    'message' => __('No drivers available in your area.'),
                    'wave' => $nextWave,
                ];
            }

            // Otherwise, just return message to try again
            return [
                'success' => false,
                'message' => __('No drivers available. Please try again.'),
                'wave' => $nextWave,
                'drivers_notified' => 0,
            ];
        }

        // Take up to drivers_per_wave drivers
        $driversToNotify = array_slice($availableDrivers, 0, $this->getDriversPerWave());

        // Notify each driver
        $notifiedCount = $this->notifyDrivers($trip, $driversToNotify);

        // Update wave number
        Redis::set("trip:{$trip->id}:current_wave", $nextWave);

        Log::info('Wave search completed', [
            'trip_id' => $trip->id,
            'wave' => $nextWave,
            'notified' => $notifiedCount
        ]);

        return [
            'success' => true,
            'message' => __('Searching for drivers...'),
            'wave' => $nextWave,
            'drivers_notified' => $notifiedCount,
            'radius' => $currentRadius,
        ];
    }

    /**
     * Execute a single wave of driver search (used by ProcessDriverSearch job)
     */
    public function searchWave(Trip $trip, int $waveNumber): array
    {
        Log::info('Executing search wave', [
            'trip_id' => $trip->id,
            'wave' => $waveNumber
        ]);

        // Get search parameters
        $currentRadius = $this->calculateRadius($waveNumber);
        $notifiedDriverIds = Redis::smembers("trip:{$trip->id}:notified_drivers") ?: [];

        // Find available drivers
        $availableDrivers = $this->getAvailableDrivers(
            (float) $trip->pickup_lat,
            (float) $trip->pickup_long,
            $currentRadius,
            $trip->vehicle_type_id,
            $notifiedDriverIds
        );

        Log::info('Search wave results', [
            'trip_id' => $trip->id,
            'wave' => $waveNumber,
            'radius' => $currentRadius,
            'drivers_found' => count($availableDrivers)
        ]);

        if (empty($availableDrivers)) {
            return [
                'driver_found' => false,
                'drivers_notified' => 0,
            ];
        }

        // Notify drivers (up to drivers_per_wave)
        $driversToNotify = array_slice($availableDrivers, 0, $this->getDriversPerWave());
        $notifiedCount = $this->notifyDrivers($trip, $driversToNotify);

        // Update Redis tracking
        Redis::set("trip:{$trip->id}:current_wave", $waveNumber);
        Redis::set("trip:{$trip->id}:current_radius", $currentRadius);

        return [
            'driver_found' => false, // Will be true if driver accepts during wave
            'drivers_notified' => $notifiedCount,
        ];
    }

    /**
     * Calculate search radius for a given wave number
     */
    private function calculateRadius(int $waveNumber): float
    {
        return $this->getInitialRadiusKm() + (($waveNumber - 1) * $this->getRadiusIncrementKm());
    }

    /**
     * Find available drivers within radius using proximity search
     */
    public function getAvailableDrivers(
        float $lat,
        float $long,
        float $radiusKm,
        int $vehicleTypeId,
        array $excludedDriverIds = []
    ): array {
        // Eloquent scopes + bounding box prefilter + Haversine distance filter
        $drivers = Driver::query()
            ->approved()
            ->online()
            ->userActive()
            ->withUserLocation()// has setted lat , long in users table 
            ->hasVehicleType($vehicleTypeId)
            ->withoutActiveTrips()
            ->whereNotIn('drivers.id', $excludedDriverIds)
            ->withinBoundingBox($lat, $long, $radiusKm)
            ->withinDistance($lat, $long, $radiusKm)
            ->get();

        // Filter out drivers with active requests
        $availableDrivers = $drivers->filter(function ($driver) {
            return !$this->hasActiveRequest($driver->id);
        });

        return $availableDrivers->pluck('id')->toArray();
    }

    /**
     * Send notifications to drivers
     */
    public function notifyDrivers(Trip $trip, array $driverIds): int
    {
        $notifiedCount = 0;
        $acceptanceTime = (int) (setting('general', 'driver_acceptance_time') ?: 60);

        foreach ($driverIds as $driverId) {
            // Double-check driver doesn't have active request
            if ($this->hasActiveRequest($driverId)) {
                Log::info('Driver already has active request, skipping', ['driver_id' => $driverId]);
                continue;
            }

            // Add to notified drivers set
            Redis::sadd("trip:{$trip->id}:notified_drivers", $driverId);

            // Set active request for driver
            $this->setActiveRequest($driverId, $trip->id);

            // Get driver's current location for distance calculation
            $driver = Driver::with('user')->find($driverId);
            $driverLat = $driver && $driver->user ? (float) $driver->user->latest_lat : null;
            $driverLong = $driver && $driver->user ? (float) $driver->user->latest_long : null;

            // Broadcast new trip request to driver with location for distance calculation
            event(new TripRequestSent($trip, $driverId, $acceptanceTime, $driverLat, $driverLong));

            $notifiedCount++;

            Log::info('Driver notified', [
                'trip_id' => $trip->id,
                'driver_id' => $driverId,
                'expires_in' => $acceptanceTime
            ]);
        }

        // Set/update trip expiration timestamp (extends window for multiple waves)
        $expiresAt = now()->addSeconds($acceptanceTime)->timestamp;
        Redis::set("trip:{$trip->id}:expires_at", $expiresAt);

        // Dispatch single expiration job for trip (ShouldBeUnique prevents duplicates)
        ExpireTripRequest::dispatch($trip->id)
            ->delay(now()->addSeconds($acceptanceTime));

        Log::info('Trip expiration job dispatched', [
            'trip_id' => $trip->id,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'drivers_notified' => $notifiedCount
        ]);

        return $notifiedCount;
    }

    /**
     * Check if driver has an active trip request
     */
    public function hasActiveRequest(int $driverId, ?int $tripId = null): bool
    {
        if ($tripId === null) {
            // Check if driver has any active request
            return Redis::exists("driver:{$driverId}:active_request") > 0;
        }
        
        // Check if driver has active request for specific trip
        $activeTrip = Redis::get("driver:{$driverId}:active_request");
        return $activeTrip && (int)$activeTrip === $tripId;
    }

    /**
     * Set active request for driver with TTL
     */
    public function setActiveRequest(int $driverId, int $tripId): void
    {
        $acceptanceTime = (int) (setting('general', 'driver_acceptance_time') ?: 60);
        // Add buffer time to ensure key exists when expiration job runs
        $ttl = $acceptanceTime + 5; // Extra 10 seconds buffer
        Redis::setex("driver:{$driverId}:active_request", $ttl, $tripId);
    }

    /**
     * Clear active request for driver
     */
    public function clearActiveRequest(int $driverId): void
    {
        Redis::del("driver:{$driverId}:active_request");
        Log::info('Cleared active request for driver', ['driver_id' => $driverId]);
    }

    /**
     * Initialize Redis tracking for a new trip search
     */
    private function initializeSearchTracking(int $tripId): void
    {
        $initialRadius = $this->getInitialRadiusKm();
        Redis::set("trip:{$tripId}:current_wave", 0);
        Redis::set("trip:{$tripId}:current_radius", $initialRadius);
        Redis::del("trip:{$tripId}:notified_drivers");

        Log::info('Initialized search tracking', [
            'trip_id' => $tripId,
            'initial_radius' => $initialRadius
        ]);
    }

    /**
     * Handle when no driver is found after all waves
     */
    public function handleNoDriverFound(Trip $trip): void
    {
        // Update trip status
        $trip->update(['status' => TripStatus::NO_DRIVER_FOUND]);

        // Broadcast event to client
        event(new TripNoDriverFound($trip));

        // Clear Redis data including all driver active requests
        // This will broadcast TripRequestExpired with reason 'no_driver' to shared channel
        $this->clearTripSearchData($trip->id, true, 'no_driver');

        Log::info('No driver found, trip updated', ['trip_id' => $trip->id]);
    }

    /**
     * Clear all Redis data for a trip search
     */
    public function clearTripSearchData(int $tripId, bool $clearDriverRequests = false, ?string $reason = null): void
    {
        if ($clearDriverRequests) {
            // Set resolved flag (signals ExpireTripRequest job to exit immediately)
            Redis::setex("trip:{$tripId}:resolved", 600, 1);

            $notifiedDrivers = Redis::smembers("trip:{$tripId}:notified_drivers") ?: [];

            // Clear active requests for all notified drivers
            foreach ($notifiedDrivers as $driverId) {
                $this->clearActiveRequest($driverId);
            }

            // Broadcast expiration to shared channel (all notified drivers will receive)
            if (count($notifiedDrivers) > 0) {
                event(new TripRequestExpired($tripId));
                Log::info('Broadcast trip expiration to shared channel', [
                    'trip_id' => $tripId,
                    'reason' => $reason ?: 'cancelled',
                    'notified_drivers' => count($notifiedDrivers)
                ]);
            }
        }

        // Clear trip search data
        Redis::del("trip:{$tripId}:current_wave");
        Redis::del("trip:{$tripId}:current_radius");
        Redis::del("trip:{$tripId}:notified_drivers");
        Redis::del("trip:{$tripId}:search_started");

        Log::info('Cleared trip search data', ['trip_id' => $tripId, 'cleared_driver_requests' => $clearDriverRequests]);
    }

    /**
     * Get notified driver IDs for a trip
     */
    public function getNotifiedDrivers(int $tripId): array
    {
        return Redis::smembers("trip:{$tripId}:notified_drivers") ?: [];
    }
}

