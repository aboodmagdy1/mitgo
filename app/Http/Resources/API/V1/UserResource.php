<?php

namespace App\Http\Resources\API\V1;

use App\Enums\Users\ProfileStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->roles->last();// solve this issue
        $data = [];
        $data['role'] = $role->name;
        if($role->name == 'client'){
            $data += [
                'id'=>$this->id,
                'phone' => $this->phone,
                'name' => $this->name,
                'lat' => $this->latest_lat,
                'long' => $this->latest_long,
                'address' => $this->address,
                'avatar' => $this->getFirstMediaUrl('avatar'),
            ];
        }
        if($role->name == 'driver'){
            $data += [
                'id'=>$this->driver?->id,
                'user_id'=>$this->id,
                'phone' => $this->phone,
                'name' => $this->name,
                'lat' => $this->latest_lat,
                'long' => $this->latest_long,
                'address' => $this->address,
                'avatar' => $this->getFirstMediaUrl('avatar'),
                'status' => $this->driver?->status ?? 0
            ];
        }
        return $data;
    }
}
