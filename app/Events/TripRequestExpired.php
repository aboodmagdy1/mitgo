<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripRequestExpired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tripId;
    public ?string $reason;
    public ?int $driverId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $tripId, ?string $reason = null, ?int $driverId = null)
    {
        $this->tripId = $tripId;
        $this->reason = $reason;
        $this->driverId = $driverId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        // If driverId provided, broadcast only to that driver's personal channel
        if ($this->driverId) {
            return new Channel("driver.{$this->driverId}");
        }
        
        // Otherwise use shared channel for all notified drivers
        return new Channel("trip-request.{$this->tripId}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'trip_request_expired';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): ?array
    {
        $data = [
            'trip_id' => $this->tripId,
        ];
        return null;
    }
}

