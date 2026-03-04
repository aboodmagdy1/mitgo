<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $layout = App::getLocale() == 'ar' ? $this->page_layout_ar : $this->page_layout_en;
        $route = Route::is('pages.show') ;
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'layout' => $this->when($route, $layout),
        ];
    }
}
