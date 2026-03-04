<?php

namespace App\Http\Resources\API\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => Carbon::parse($this->created_at)->diffForHumans(),
        ];

        // Include user information if requested
        if ($this->relationLoaded('user')) {
            $data['user'] = [
                'id' => $this->user->id,
                'name' => $this->user->first_name . ' ' . $this->user->last_name,
                'avatar' => $this->user->getFirstMediaUrl('avatar'),
            ];
        }
        return $data;
    }
}
