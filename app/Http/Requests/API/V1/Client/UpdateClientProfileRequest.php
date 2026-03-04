<?php

namespace App\Http\Requests\API\V1\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UpdateClientProfileRequest extends FormRequest
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
        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:255|unique:users,phone,' . $user->id,
            'city_id' => 'nullable|integer|exists:cities,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
