<?php

namespace App\Http\Resources\API\V1\Driver;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user'=> (object)[
                'name'=>$this->user->name,
                'avatar'=>$this->user->getFirstMediaUrl('avatar'),
            ],
            'rate'=>$this->rating,
            'comment'=>$this->ratingComment->comment,
            'created_at' => Carbon::parse($this->created_at)->translatedFormat('Y M d'),
        ];
    }
}
