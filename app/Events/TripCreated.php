<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCreated
{
    use Dispatchable, SerializesModels;

    public Trip $trip;

    /**
     * Create a new event instance.
     */
    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }
}

