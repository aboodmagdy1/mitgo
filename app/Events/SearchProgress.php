<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SearchProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tripId;
    public int $currentWave;
    public int $totalWaves;
    public int $driversNotified;
    public string $status;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $tripId,
        int $currentWave,
        int $totalWaves,
        int $driversNotified,
        string $status = 'searching'
    ) {
        $this->tripId = $tripId;
        $this->currentWave = $currentWave;
        $this->totalWaves = $totalWaves;
        $this->driversNotified = $driversNotified;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel("trip.{$this->tripId}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'search_progress';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'current_wave' => $this->currentWave,
            'total_waves' => $this->totalWaves,
        ];
    }
}

