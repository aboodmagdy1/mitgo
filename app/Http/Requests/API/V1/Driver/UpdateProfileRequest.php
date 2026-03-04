<?php

namespace App\Http\Requests\API\V1\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UpdateProfileRequest extends FormRequest
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
        $user = Auth::user();
        $driverId = $user->driver ? $user->driver->id : null;
        
        return [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255|unique:users,phone,' . $user->id,
            'absher_phone' => $driverId ? 'nullable|string|max:255|unique:drivers,absher_phone,' . $driverId : 'nullable|string|max:255',
            'national_id' => $driverId ? 'nullable|string|max:255|unique:drivers,national_id,' . $driverId : 'nullable|string|max:255',
            'license_number' => $driverId ? 'nullable|string|max:255|unique:drivers,license_number,' . $driverId : 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'city_id' => 'nullable|integer|exists:cities,id',
            'seats' => 'nullable|integer|min:1',
            'color' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:255',
            'car_license_number' => 'nullable|string|max:255',
            'brand_model_id' => 'nullable|integer|exists:vehicle_brand_models,id',
        ];
    }

    public function validationData(): array
    {
        $data = parent::validationData();

        if (isset($data['phone'])) {
            $rawPhone = (string) ($data['phone'] ?? '');

            // Keep only digits
            $digitsOnlyPhone = preg_replace('/\D+/', '', $rawPhone) ?? '';

            // If phone starts with 05..., drop the leading 0 so 0512346789 == 512346789
            if (Str::startsWith($digitsOnlyPhone, '05')) {
                $digitsOnlyPhone = substr($digitsOnlyPhone, 1);
            }

            // Add country code 966 for unique validation check to match database format
            $data['phone'] = '966' . $digitsOnlyPhone;
        }

        return $data;
    }
}
