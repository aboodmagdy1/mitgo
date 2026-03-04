<?php

namespace Modules\Chat\Transformers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Enums\MessageType;

class MessageResource extends JsonResource
{
    
    public function __construct($resource)
    {
        parent::__construct($resource);
    }
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // $this->type is a MessageType enum instance due to enum cast

        $data =  [
            'id' => $this->id,
            'content' => $this->content,
            'sender_id' => $this->sender_id,
            'created_at' => Carbon::parse($this->created_at)->format('H:i'),
        ];
        return $data;
    }
}
