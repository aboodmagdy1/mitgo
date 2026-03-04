<?php

namespace App\Http\Resources\API\V1\Driver;

use App\Enums\TripStatus;
use App\Http\Resources\API\V1\Driver\TripInvoiceResource;
use App\Http\Resources\API\V1\Driver\TripRiderResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningTripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'amount' => $this->payment->driver_earning,
            'date'=>Carbon::parse($this->end_at)->translatedFormat('Y M,d'),
        ];
    }

  
}

