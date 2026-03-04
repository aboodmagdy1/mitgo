<?php

namespace App\Http\Requests\API\V1\Client\Trips;

use Illuminate\Foundation\Http\FormRequest;

class CreateTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_long' => 'required|numeric|between:-180,180',
            'pickup_address' => 'nullable|string|max:500',
            'dropoff_lat' => 'required|numeric|between:-90,90',
            'dropoff_long' => 'required|numeric|between:-180,180',
            'dropoff_address' => 'nullable|string|max:500',
            'scheduled_date' => 'nullable|date|after:now',
            'scheduled_time' => 'nullable|date_format:H:i',
            'coupon'=>'nullable|string|exists:coupons,code'
        ];
    }
}

