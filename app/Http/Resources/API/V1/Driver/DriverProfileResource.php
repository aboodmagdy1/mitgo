<?php

namespace App\Http\Resources\API\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // User data
            'avatar' => $this->getFirstMediaUrl('avatar'),
            'name' => $this->name,
            'absher_phone' => $this->driver->absher_phone,
            'national_id' => $this->driver->national_id,
            'date_of_birth' => $this->driver->date_of_birth?->format('Y-m-d'),
            'city' => (object) [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ],
            'seats' => $this->driver->vehicle->seats,
            'color' => $this->driver->vehicle->color,
            'plate_number' => $this->driver->vehicle->plate_number,
            'car_license_number' => $this->driver->vehicle->license_number,
            'brand' =>(object)  [
                'id' => $this->driver->vehicle->vehicleBrandModel->vehicleBrand->id,
                'name' => $this->driver->vehicle->vehicleBrandModel->vehicleBrand->name,
            ],
            'model' =>(object) [
                'id' => $this->driver->vehicle->vehicleBrandModel->id,
                'name' => $this->driver->vehicle->vehicleBrandModel->name,
            ],

            
        ];
    }
}
