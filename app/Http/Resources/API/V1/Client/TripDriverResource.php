<?php

namespace App\Http\Resources\API\V1\Client;

use App\Http\Resources\API\V1\TripVehicleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripDriverResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user->name,
            'phone' => $this->user->phone,
            'rating' => $this->rating,
            'avatar' => $this->user->getFirstMediaUrl('avatar'),
            'current_lat' =>  $this->user->latest_lat,
            'current_long' =>  $this->user->latest_long,
            'vehicle' => TripVehicleResource::make($this->vehicle),
        ];
    }
}
