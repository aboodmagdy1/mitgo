<?php

namespace App\Http\Resources\API\V1\Driver;

use App\Http\Resources\API\V1\PaymentMethodsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use function App\Helpers\setting;

class TripInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->payment;
        
        if ($payment) {
            // Use actual payment values for completed trips
            $total = $payment->total_amount;
            $commission = $payment->commission_amount;
            $earning = $payment->driver_earning;
            $discount = $payment->coupon_discount;
        } else {
            // Calculate estimated values for non-completed trips (scheduled/in-progress)
            $estimatedFare = $this->estimated_fare ?? 0;
            $commissionRate = setting('general', 'commission_rate') ?? 10;
            
            // Calculate estimated commission and earning
            $commission = ($estimatedFare * $commissionRate) / 100;
            $earning = $estimatedFare - $commission;
            $total = $estimatedFare;
            $discount = 0;
        }

        return [
            'vehicle_type' => $this->vehicleType?->getTranslation('name', 'ar'),
            'vehicle_type_en' => $this->vehicleType?->getTranslation('name', 'en'),
            'payment_method' => PaymentMethodsResource::make($this->paymentMethod),
            'earning' =>  $earning,
            'commission' =>     $commission,
            'total' =>  $total,
            'final_amount' => $this->payment->final_amount,
            'discount' =>  $discount,
        ];
    }


}

