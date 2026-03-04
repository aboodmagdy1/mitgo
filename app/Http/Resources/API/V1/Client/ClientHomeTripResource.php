<?php

namespace App\Http\Resources\API\V1\Client;

use App\Enums\TripStatus;
use App\Http\Resources\API\V1\TripDriverResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Redis;
use function App\Helpers\setting;

class ClientHomeTripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Base data for all statuses
        $data = [
            'trip_id' => $this->id,
            'status' => [
                'id' => $this->status?->value,
                'name' => $this->status?->label('ar'),
                'name_en' => $this->status?->label('en'),
            ],
            'pickup' => [
                'lat' =>  $this->pickup_lat,
                'long' =>  $this->pickup_long,
                'address' => $this->pickup_address,
            ],
            'dropoff' => [
                'lat' =>  $this->dropoff_lat,
                'long' =>  $this->dropoff_long,
                'address' => $this->dropoff_address,
            ],
        ];

        // If trip is in SEARCHING status (waiting for driver)
        if ($this->status === TripStatus::SEARCHING) {
            $data['estimated_fare'] =  $this->estimated_fare;
            $data['estimated_duration'] = $this->estimated_duration;
            $data['estimated_distance'] =  $this->distance;
            $data['vehicle_type'] = [
                'id' => $this->vehicleType->id,
                'name' => $this->vehicleType->getTranslation('name', 'ar'),
                'name_en' => $this->vehicleType->getTranslation('name', 'en'),
            ];
            $data['payment_method'] = [
                'id' => $this->paymentMethod->id,
                'name' => $this->paymentMethod->getTranslation('name', 'ar'),
                'name_en' => $this->paymentMethod->getTranslation('name', 'en'),
            ];
            
            // Get search progress info from Redis
            $currentWave = (int) Redis::get("trip:{$this->id}:current_wave") ?: 0;
            $maxWaves = setting('general', 'search_wave_count') ?: 10;
            
            $data['search_progress'] = [
                'current_wave' => $currentWave,
                'total_waves' => $maxWaves,
                'progress_percentage' => $maxWaves > 0 ? round(($currentWave / $maxWaves) * 100) : 0,
            ];
        }

        // If trip is IN_ROUTE_TO_PICKUP (driver accepted and coming)
        if ($this->status === TripStatus::IN_ROUTE_TO_PICKUP) {
            $data['estimated_fare'] =  $this->estimated_fare;
            $data['driver'] = TripDriverResource::make($this->driver);
        }

        // If trip is PICKUP_ARRIVED (driver waiting at pickup)
        if ($this->status === TripStatus::PICKUP_ARRIVED) {
            $data['arrived_at'] = $this->arrived_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
            $data['driver'] = TripDriverResource::make($this->driver);
        }

        // If trip is IN_PROGRESS (trip started)
        if ($this->status === TripStatus::IN_PROGRESS) {
            $data['started_at'] = $this->started_at?->format('Y-m-d H:i:s');
            $data['estimated_duration'] = $this->estimated_duration;
            $data['estimated_fare'] =  $this->estimated_fare;
            $data['driver'] = TripDriverResource::make($this->driver);
        }

        // If trip is COMPLETED_PENDING_PAYMENT or COMPLETED (trip ended)
        if (in_array($this->status, [TripStatus::COMPLETED_PENDING_PAYMENT, TripStatus::COMPLETED])) {
            $data['ended_at'] = $this->ended_at?->format('Y-m-d H:i:s');
            $data['started_at'] = $this->started_at?->format('Y-m-d H:i:s');
            $data['actual_duration'] = $this->actual_duration;
            $data['actual_distance'] =  $this->actual_distance;
            $data['actual_fare'] =  $this->actual_fare;
            $data['driver'] = TripDriverResource::make($this->driver);
            
            if ($this->payment) {
                $data['payment'] = [
                    'method_id' => $this->payment_method_id,
                    'final_amount' => $this->payment->final_amount,
                    'total_amount' => $this->payment->total_amount, // Use total_amount (what customer pays)
                    'status' => $this->payment->status,
                ];
            }
        }

        // If trip is fully COMPLETED (add completed_at and coupon discount)
        if ($this->status === TripStatus::COMPLETED) {
            $data['completed_at'] = now()->format('Y-m-d H:i:s');
            if ($this->payment) {
                $data['payment']['coupon_discount'] =  $this->payment->coupon_discount;
            }
        }

        return $data;
    }
}

