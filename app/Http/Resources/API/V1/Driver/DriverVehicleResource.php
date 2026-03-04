<?php

namespace App\Http\Resources\API\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverVehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'color' => $this->color,
            'plate_number' => $this->plate_number,
            'license_number' => $this->license_number,
            'brand' => $this->vehicleBrandModel->vehicleBrand->name,
            'model' => $this->vehicleBrandModel->name,
            'icon' => $this->vehicleType->getFirstMediaUrl('icon'),
        ];
    }
}
