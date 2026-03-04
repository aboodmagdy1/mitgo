<?php

namespace App\Http\Resources\API\V1\Client;

use App\Enums\TripStatus;
use App\Http\Resources\API\V1\TripDriverResource;    
use App\Http\Resources\API\V1\TripInvoiceResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeTripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'status' => [
                'id' => $this->status?->value,
                'name' => $this->status?->label('ar'),
                'name_en' => $this->status?->label('en'),
            ],
            'pickup' => [
                'lat' => $this->pickup_lat,
                'long' => $this->pickup_long,
                'address' => $this->pickup_address,
            ],
            'dropoff' => [
                'lat' => $this->dropoff_lat,
                'long' => $this->dropoff_long,
                'address' => $this->dropoff_address,
            ],        ];
        
        return $data;
    }
}

