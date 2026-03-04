<?php

namespace App\Http\Requests\API\V1\Driver\Trips;

use App\Enums\TripStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateStatusRequest extends FormRequest
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
        $action = $this->input('action');
        
        $rules = [
            'action' => ['required', 'integer', new Enum(TripStatus::class)],
        ];
        
        // Add conditional rules based on action
        if ($action == TripStatus::IN_PROGRESS->value) {
            // Start trip needs pickup coordinates
            $rules['pickup_lat'] = ['required', 'numeric', 'between:-90,90'];
            $rules['pickup_long'] = ['required', 'numeric', 'between:-180,180'];
            $rules['pickup_address'] = ['required', 'string', 'max:500'];
        }
        
        if ($action == TripStatus::COMPLETED->value || $action == TripStatus::COMPLETED_PENDING_PAYMENT->value) {
            // End trip needs dropoff coordinates
            $rules['dropoff_lat'] = ['required', 'numeric', 'between:-90,90'];
            $rules['dropoff_long'] = ['required', 'numeric', 'between:-180,180'];
            $rules['dropoff_address'] = ['required', 'string', 'max:500'];
        }
        
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'pickup_lat.required' => 'Pickup latitude required when starting trip',
            'dropoff_lat.required' => 'Dropoff latitude required when ending trip',
            'dropoff_long.required' => 'Dropoff longitude required when ending trip',
            'dropoff_address.required' => 'Dropoff address required when ending trip',
            'pickup_long.required' => 'Pickup longitude required when starting trip',
            'pickup_address.required' => 'Pickup address required when starting trip',
            'pickup_lat.numeric' => 'Pickup latitude must be a number',
            'pickup_long.numeric' => 'Pickup longitude must be a number',
            'dropoff_lat.numeric' => 'Dropoff latitude must be a number',
            'dropoff_long.numeric' => 'Dropoff longitude must be a number',
            'pickup_address.string' => 'Pickup address must be a string',
            'dropoff_address.string' => 'Dropoff address must be a string',
            'pickup_address.max' => 'Pickup address must be less than 500 characters',
            'dropoff_address.max' => 'Dropoff address must be less than 500 characters',
        ];
    }
}

