<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripVehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'model' => $this->vehicleBrandModel?->getTranslation('name', 'ar') ?? null,
            'model_en' => $this->vehicleBrandModel?->getTranslation('name', 'en') ?? null,
            'color' => $this->color ?? null,
            'plate_number' => $this->plate_number ?? null,
        ];
    }
}

