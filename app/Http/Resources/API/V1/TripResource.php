<?php

namespace App\Http\Resources\API\V1;

use App\Enums\TripStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data =  [
            'id' => $this->id,
            'pickup' => [
                'lat' => $this->pickup_lat,
                'long' => $this->pickup_long,
                'address' => $this->pickup_address,
            ],
            'dropoff' => [
                'lat' => $this->dropoff_lat,
                'long' => $this->dropoff_long,
                'address' => $this->dropoff_address,
            ],
            
            'payment_method' => PaymentMethodsResource::make($this->paymentMethod)           
        ];

        if($this->status == TripStatus::COMPLETED){
            $data['duration'] = $this->actual_duration;
            $data['cost'] = $this->actual_fare;
        }

        if(Auth::user()->hasRole('driver')){
            $data['driver'] = null;
        }else{
            if($this->driver_id){
            $data['driver'] = [
                'id' => $this->driver?->id,
                'name' => $this->driver?->name,
                'phone' => $this->driver?->phone,
                'rating' => $this->driver?->rating,
            ];   
            }
        }


        return $data;
    }
}

