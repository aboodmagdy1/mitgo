<?php

namespace App\Http\Resources\API\V1\Driver;

use App\Enums\TripStatus;
use App\Http\Resources\API\V1\Driver\TripInvoiceResource;
use App\Http\Resources\API\V1\Driver\TripRiderResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyTripsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'trip_id' => $this->id,
            'status' => [
                'id' => $this->status?->value,
                'name' => $this->status?->label('ar'),
                'name_en' => $this->status?->label('en'),
            ],            
            'dropoff' => [
                'lat' => $this->dropoff_lat,
                'long' => $this->dropoff_long,
                'address' => $this->dropoff_address,
            ],
            'pickup' => [
                'lat' => $this->pickup_lat,
                'long' => $this->pickup_long,
                'address' => $this->pickup_address,
            ],
            'cost' => $this->getCostByStatus(),
            'created_at_date' => $this->status == TripStatus::SCHEDULED ? Carbon::parse($this->scheduled_date)->translatedFormat('Y M,d') : Carbon::parse($this->created_at)->translatedFormat('Y M,d'),//12 فبراير, 2025
            'created_at_time' =>  $this->status == TripStatus::SCHEDULED ? Carbon::parse($this->scheduled_time)->translatedFormat('h:i A') : Carbon::parse($this->created_at)->translatedFormat('h:i A'),//12:00 م
        ];
        
        // Include detailed information for trip details view
        if($request->route()->getName() == 'driver.trips.show'){
            unset($data['cost']);
            $data['pickup_address'] = $this->pickup_address;
            $data['rider']=TripRiderResource::make($this->user);
            $data['distance'] = $this->status == TripStatus::COMPLETED ? $this->distance : null;
            $data['duration'] = $this->status == TripStatus::COMPLETED ? $this->actual_duration : null;
            $data['invoice'] = $this->getInvoiceByStatus();
        }
            
        return $data;
    }

    /**
     * Get cost based on trip status
     */
    private function getCostByStatus()
    {
        return match(true) {
            // Completed trips - use payment total amount
            $this->status === TripStatus::COMPLETED => 
                $this->payment?->total_amount ?? $this->actual_fare ?? $this->estimated_fare,
            
            // Scheduled trips - show estimated fare
            $this->status === TripStatus::SCHEDULED => 
                $this->estimated_fare ?? 0,
            
            // Cancelled trips without fee
            $this->isCancelled() => 0,
            
            // Default - show estimated fare
            default => 0,   
        };
    }

    /**
     * Get driver data based on trip status
     */
    // private function getDriverByStatus()
    // {
    //     return match(true) {
    //         // Completed trips - always show driver
    //         $this->status === TripStatus::COMPLETED => 
    //             TripDriverResource::make($this->driver),
            
            
    //         // Scheduled trips - show driver if assigned
    //         $this->status === TripStatus::SCHEDULED => 
    //             $this->driver ? TripDriverResource::make($this->driver) : null,
            
    //         // Cancelled trips - show driver if was assigned (for reference)
    //         $this->isCancelled() => 
    //             $this->driver ? TripDriverResource::make($this->driver) : null,
            
    //         // Default - no driver
    //         default => null,
    //     };
    // }

    /**
     * Get invoice data based on trip status
     */
    private function getInvoiceByStatus()
    {
        return match(true) {
            // Completed trips - show invoice with actual payment data
            $this->status === TripStatus::COMPLETED => 
                TripInvoiceResource::make($this),
            
            // Scheduled trips - show invoice with estimated values
            $this->status === TripStatus::SCHEDULED => 
                TripInvoiceResource::make($this),
            
            // Cancelled trips - no invoice
            $this->isCancelled() => null,
            
            // All other statuses - no invoice yet
            default => null,
        };
    }
}

