<?php

namespace App\Http\Requests\API\V1\Client\Trips;

use Illuminate\Foundation\Http\FormRequest;

class VehicleTypesListRequest extends FormRequest
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
            'pickup_lat' => 'required|numeric',
            'pickup_long' => 'required|numeric',
            'dropoff_lat' => 'nullable|numeric',
            'dropoff_long' => 'nullable|numeric'  
        ];
    }
}
