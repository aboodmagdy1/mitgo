<?php

namespace App\Http\Resources\API\V1\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];
            $data += [
                'avatar' => $this->getFirstMediaUrl('avatar'),
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'city' => [
                    'id' => $this->city->id,
                    'name' => $this->city->name,
                ],
            ];
          
        return $data;
    }
}
