<?php

namespace App\Http\Resources\API\V1\Driver;

use App\Enums\TripStatus;
use App\Http\Resources\API\V1\TripRiderResource;
use App\Http\Resources\API\V1\TripVehicleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use function App\Helpers\setting;
use function App\Helpers\get_distance_and_duration;

class DriverHomeTripResource extends JsonResource
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

        // If trip is in SEARCHING status (pending request for driver)
        if ($this->status === TripStatus::SEARCHING) {
            $acceptanceTime = setting('general', 'driver_acceptance_time') ?: 60;
            $expiresAt = Redis::get("trip:{$this->id}:expires_at");
            
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
            $data['rider'] = TripRiderResource::make($this->user);
            $data['acceptance_time'] = $acceptanceTime;
            $data['expires_at'] = $expiresAt ? date('c', $expiresAt) : now()->addSeconds($acceptanceTime)->toISOString();
            
            // Calculate distance from driver to pickup if driver location is available
            $user = Auth::user();
            if ($user && $user->latest_lat && $user->latest_long) {
                try {
                    $pickupDistance = get_distance_and_duration(
                        [$user->latest_lat, $user->latest_long],
                        [$this->pickup_lat, $this->pickup_long]
                    );
                    
                    $data['pickup_arrive_distance'] = $pickupDistance['distance']; // in km
                    $data['pickup_arrive_duration'] = $pickupDistance['duration']; // in minutes
                } catch (\Exception $e) {
                    $data['pickup_arrive_distance'] = null;
                    $data['pickup_arrive_duration'] = null;
                }
            } else {
                $data['pickup_arrive_distance'] = null;
                $data['pickup_arrive_duration'] = null;
            }
        }

        // If trip is IN_ROUTE_TO_PICKUP (driver accepted)
        if ($this->status === TripStatus::IN_ROUTE_TO_PICKUP) {
            $data['estimated_fare'] =  $this->estimated_fare;
            $data['rider'] = TripRiderResource::make($this->user);
        }

        // If trip is PICKUP_ARRIVED (driver arrived at pickup)
        if ($this->status === TripStatus::PICKUP_ARRIVED) {
            $data['arrived_at'] = $this->arrived_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
            $data['rider'] = TripRiderResource::make($this->user);
        }

        // If trip is IN_PROGRESS (trip started)
        if ($this->status === TripStatus::IN_PROGRESS) {
            $data['started_at'] = $this->started_at?->format('Y-m-d H:i:s');
            $data['estimated_duration'] = $this->estimated_duration;
            $data['estimated_fare'] =  $this->estimated_fare;
            $data['rider'] = TripRiderResource::make($this->user);
        }

        // If trip is COMPLETED_PENDING_PAYMENT or COMPLETED (trip ended)
        if (in_array($this->status, [TripStatus::COMPLETED_PENDING_PAYMENT, TripStatus::COMPLETED])) {
            $data['ended_at'] = $this->ended_at?->format('Y-m-d H:i:s');
            $data['started_at'] = $this->started_at?->format('Y-m-d H:i:s');
            $data['actual_duration'] = $this->actual_duration;
            $data['actual_distance'] =  $this->actual_distance;
            $data['actual_fare'] =  $this->actual_fare;
            $data['rider'] = TripRiderResource::make($this->user);
            $data['invoice'] = TripInvoiceResource::make($this);
        }

        return $data;
    }
}

