<?php

namespace App\Http\Requests\API\V1\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RegisterReqest extends FormRequest
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
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'name' => 'required|string|max:255',
            'absher_phone' => 'required|string|max:255',
            'national_id' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'city_id' => 'required|integer|exists:cities,id',
            'seats' => 'required|string',
            'color' => 'required|string|max:255',
            'plate_number' => 'required|string|max:255',
            'car_license_number' => 'required|string|max:255',
            'brand_model_id' => 'required|integer|exists:vehicle_brand_models,id',
            'gender' => 'nullable|in:male,female',
        ];
    }

}
