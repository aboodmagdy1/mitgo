<?php

namespace App\Http\Resources\API\V1;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResrouce extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data =  [
            'number' => $this->id,
            'amount' =>(string) $this->amount / 100,
            'created_at' => $this->created_at->diffForHumans(),
        ];

        if(isset($this->meta['type']) && $this->meta['type'] == 'trip_earning'){
            $data['type'] = 'trip_earning';
            $data['description'] = __('Trip earning for trip #:number', ['number' => $this->meta['trip_id']]);
            $trip = Trip::with('payment')->find($this->meta['trip_id']);
            if($trip){
                $data['invoice'] = [
                    'total'=>$trip->payment->total_amount,
                    'app_commission'=>$trip->payment->commission_amount,
                    'earning'=>$trip->payment->driver_earning,
                ];
            }
           
        }
        if(isset($this->meta['type']) && $this->meta['type'] == 'app_commission'){
            $data['type'] = 'app_commission';
            $data['description'] = __('App commission for trip #:number', ['number' => $this->meta['trip_id']]);
        }
        else{
            $data['type'] = $this->type;
        }
        return $data;
    }
}
