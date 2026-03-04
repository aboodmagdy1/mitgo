<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripNoShow implements ShouldBroadcast
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
        return 'trip_no_show';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $this->trip->load(['driver.user', 'driver.vehicle', 'user']);

        return [
            'trip_id' => $this->trip->id,
            'status' => [
                'id' => $this->trip->status->value,
                'name' => $this->trip->status->label('ar'),
                'name_en' => $this->trip->status->label('en'),
            ],
            'cancellation_fee' =>  $this->trip->cancellation_fee ?? 0,
            'driver' => [
                'id' => $this->trip->driver->id,
                'name' => $this->trip->driver->user->name,
                'phone' => $this->trip->driver->user->phone,
            ],
            'rider' => [
                'id' => $this->trip->user->id,
                'name' => $this->trip->user->name,
                'phone' => $this->trip->user->phone,
            ],
            'pickup' => [
                'lat' =>  $this->trip->pickup_lat,
                'long' =>  $this->trip->pickup_long,
                'address' => $this->trip->pickup_address,
            ],
            'dropoff' => [
                'lat' =>  $this->trip->dropoff_lat,
                'long' =>  $this->trip->dropoff_long,
                'address' => $this->trip->dropoff_address,
            ],
        ];
    }
}

