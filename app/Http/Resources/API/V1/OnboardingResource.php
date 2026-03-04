<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,
            'image' => $this->when($this->hasMedia('onboarding'), function () {
                return $this->getFirstMediaUrl('onboarding');
            }),
        ];
    }
}
