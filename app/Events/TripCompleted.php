<?php

namespace App\Events;

use App\Models\Trip;
use App\Http\Resources\API\V1\TripDriverResource;
use App\Http\Resources\API\V1\TripRiderResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\API\V1\Driver\TripInvoiceResource as DriverTripInvoiceResource;

class TripCompleted implements ShouldBroadcast
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
        return 'trip_completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $this->trip->load(['driver.user', 'driver.vehicle', 'user', 'payment', 'coupon']);

        return [
            'trip_id' => $this->trip->id,
            'status' => [
                'id' => $this->trip->status->value,
                'name' => $this->trip->status->label('ar'),
                'name_en' => $this->trip->status->label('en'),
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
            'completed_at' => now()->format('Y-m-d H:i:s'),
            'started_at' => $this->trip->started_at?->format('Y-m-d H:i:s'),
            'ended_at' => $this->trip->ended_at?->format('Y-m-d H:i:s'),
            'actual_duration' => $this->trip->actual_duration,
            'actual_distance' =>  $this->trip->actual_distance,
            'actual_fare' =>  $this->trip->actual_fare,
            'driver' => TripDriverResource::make($this->trip->driver)->resolve(),
            'rider' => TripRiderResource::make($this->trip->user)->resolve(),
            'payment_method' => [
                'id' => $this->trip->payment_method_id,
                'name' => app()->getLocale() === 'ar' ? $this->trip->paymentMethod->getTranslation('name', 'ar') : $this->trip->paymentMethod->getTranslation('name', 'en'),
            ],
            'invoice' => DriverTripInvoiceResource::make($this->trip),
        ];
    }
}


