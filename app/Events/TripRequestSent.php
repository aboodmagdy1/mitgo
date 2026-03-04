<?php

namespace App\Events;

use App\Models\Trip;
use App\Http\Resources\API\V1\TripRiderResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use function App\Helpers\get_distance_and_duration;

class TripRequestSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Trip $trip;
    public int $driverId;
    public int $acceptanceTime;
    public ?float $driverLat;
    public ?float $driverLong;

    /**
     * Create a new event instance.
     */
    public function __construct(Trip $trip, int $driverId, int $acceptanceTime, ?float $driverLat = null, ?float $driverLong = null)
    {
        $this->trip = $trip;
        $this->driverId = $driverId;
        $this->acceptanceTime = $acceptanceTime;
        $this->driverLat = $driverLat;
        $this->driverLong = $driverLong;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel("driver.{$this->driverId}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new_trip_request';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $this->trip->load(['vehicleType', 'paymentMethod', 'user']);

        $data = [
            'trip_id' => $this->trip->id,
            'status' => [
                'id' => $this->trip->status->value,
                'name' => $this->trip->status->label('ar'),
                'name_en' => $this->trip->status->label('en'),
            ],
            'pickup' => [
                'lat' =>$this->trip->pickup_lat,
                'long' =>$this->trip->pickup_long,
                'address' => $this->trip->pickup_address,
            ],
            'dropoff' => [
                'lat' =>$this->trip->dropoff_lat,
                'long' =>$this->trip->dropoff_long,
                'address' => $this->trip->dropoff_address,
            ],
            'estimated_fare' =>$this->trip->estimated_fare,
            'estimated_duration' => $this->trip->estimated_duration,
            'estimated_distance' =>$this->trip->distance,
            'vehicle_type' => [
                'id' => $this->trip->vehicleType->id,
                'name' => app()->getLocale() === 'ar' ? $this->trip->vehicleType->getTranslation('name', 'ar') : $this->trip->vehicleType->getTranslation('name', 'en'),
            ],
            'payment_method' => [
                'id' => $this->trip->paymentMethod->id,
                'name' => app()->getLocale() === 'ar' ? $this->trip->paymentMethod->getTranslation('name', 'ar') : $this->trip->paymentMethod->getTranslation('name', 'en'),
            ],
            'rider' => TripRiderResource::make($this->trip->user)->resolve(),
            'acceptance_time' => $this->acceptanceTime,
            'expires_at' => now()->addSeconds($this->acceptanceTime)->toISOString(),
        ];

        // Calculate distance and duration from driver's current location to pickup
        if ($this->driverLat && $this->driverLong) {
            try {
                $pickupDistance = get_distance_and_duration(
                    [$this->driverLat, $this->driverLong],
                    [ $this->trip->pickup_lat,$this->trip->pickup_long]
                );

                $data['pickup_arrive_distance'] = $pickupDistance['distance']; // in km
                $data['pickup_arrive_duration'] = $pickupDistance['duration']; // in minutes
            } catch (\Exception $e) {
                // If Google API fails, fallback to null
                $data['pickup_arrive_distance'] = null;
                $data['pickup_arrive_duration'] = null;
            }
        } else {
            $data['pickup_arrive_distance'] = null;
            $data['pickup_arrive_duration'] = null;
        }

        return $data;
    }
}

