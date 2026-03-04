<?php

namespace App\Http\Resources\API\V1\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedLocationResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'lat' => $this->lat,
            'long' => $this->long,
        ];
    }
}
