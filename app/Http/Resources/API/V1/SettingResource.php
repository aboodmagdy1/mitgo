<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Settings\GeneralSettings;
use App\Settings\SocialMediaSettings;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        if ($resource instanceof GeneralSettings) {
            return [
              
                    'name_ar' => $resource->name_ar,
                    'name_en' => $resource->name_en,
                    'email' => $resource->email,
                    'phone' => $resource->phone,
                    'logo_ar' => $resource->logo_ar,
                    'logo_ar_url' => $resource->logo_ar_url ?? null,
                    'logo_en' => $resource->logo_en,
                    'logo_en_url' => $resource->logo_en_url ?? null,
                
            ];
        }

        if ($resource instanceof SocialMediaSettings) {
            return [
                
                    'facebook' => $resource->facebook ?? null,
                    'twitter' => $resource->twitter ?? null,
                    'instagram' => $resource->instagram ?? null,
                    'linkedin' => $resource->linkedin ?? null,
                    'youtube' => $resource->youtube ?? null,
                

            ];
        }

        return [];
    }
}
