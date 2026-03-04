<?php

namespace App\Http\Resources\API\V1\Client;

use App\Http\Resources\API\V1\PaymentMethodsResource;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        
        if($this->isCancelled() && $this->cancellation_fee > 0){
            // For cancellations, final_amount equals total_amount (no coupon discount)
            $total = $payment->final_amount ?? $payment->total_amount ?? $this->cancellation_fee;
        }else{
            // Use final_amount (what customer actually pays) with fallback for backward compatibility
            $total = $payment?->final_amount ?? $payment?->total_amount ?? ($this->actual_fare ?? $this->estimated_fare ?? 0);
        }

        return [
            'vehicle_type' => $this->vehicleType?->getTranslation('name', 'ar'),
            'vehicle_type_en' => $this->vehicleType?->getTranslation('name', 'en'),
            'payment_method' => PaymentMethodsResource::make($this->paymentMethod),
            'total' =>  $total,
            'final_amount' => $this->payment->final_amount,
            'discount' => $this->payment->coupon_discount,
        ];
    }

    /**
     * Get payment status text.
     */
    private function getPaymentStatusText(int $status): string
    {
        return match($status) {
            0 => __('Pending'),
            1 => __('Completed'),
            2 => __('Failed'),
            3 => __('Refunded'),
            default => __('Unknown'),
        };
    }
}

