<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripNoDriverFound implements ShouldBroadcast
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
    public function broadcastOn(): Channel
    {
        return new Channel("trip.{$this->trip->id}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'no_driver_found';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->trip->id,
            'status' => (object)['id' => $this->trip->status->value, 'name' => $this->trip->status->name],
            'message' => __('No drivers available at this time'),
        ];
    }
}

