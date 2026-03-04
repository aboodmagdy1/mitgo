<?php

namespace App\Events;

use App\Models\Trip;
use App\Http\Resources\API\V1\TripDriverResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\API\V1\TripRiderResource;

class TripDriverAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Trip $trip;

    /**
     * Create a new event instance.
     */
    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("trip.{$this->trip->id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'driver_accepted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $this->trip->load(['driver.user', 'driver.vehicle']);

        return [
            'trip_id' => $this->trip->id,
            'status' => [
                'id' => $this->trip->status->value,
                'name' => $this->trip->status->label('ar'),
                'name_en' => $this->trip->status->label('en'),
            ],
            'driver' => TripDriverResource::make($this->trip->driver)->resolve(),
            'rider' => TripRiderResource::make($this->trip->user)->resolve(),
            'pickup' => [
                'lat' => $this->trip->pickup_lat,
                'long' => $this->trip->pickup_long,
                'address' => $this->trip->pickup_address,
            ],
            'dropoff' => [
                'lat' => $this->trip->dropoff_lat,
                'long' => $this->trip->dropoff_long,
                'address' => $this->trip->dropoff_address,
            ],
            'estimated_fare' => $this->trip->estimated_fare,
            'estimated_duration' => $this->trip->estimated_duration,
        ];
    }
}

