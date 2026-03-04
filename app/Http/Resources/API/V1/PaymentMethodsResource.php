<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodsResource extends JsonResource
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
            'name' =>  app()->getLocale() === 'ar' ? $this->getTranslation('name', 'ar') : $this->getTranslation('name', 'en'),
            'image' => $this->getFirstMediaUrl('image'),
        ];
    }
}
