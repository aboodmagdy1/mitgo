<?php

namespace Modules\Chat\Transformers;

use App\Enums\Users\UserTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageSenderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
       $role = $this->roles?->first();
       if($role && $role->name == 'client')
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->getFirstMediaUrl('avatar'),
        ];

       if($role && $role->name == 'provider')
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->getFirstMediaUrl('logo'),
       ];
       return [];
    }  
}
