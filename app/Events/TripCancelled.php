<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Trip $trip;
    public string $cancelledBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Trip $trip, string $cancelledBy = 'rider')
    {
        $this->trip = $trip;
        $this->cancelledBy = $cancelledBy;
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
        return 'trip_cancelled';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->trip->id,
            'status' => [
                'id' => $this->trip->status->value,
                'name' => $this->trip->status->label('ar'),
                'name_en' => $this->trip->status->label('en'),
            ],
            'cancelled_by' => $this->cancelledBy,
            'cancellation_fee' =>  $this->trip->cancellation_fee ?? 0,
        ];
    }
}

