<?php

namespace App\Http\Requests\API\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ActiveCodeRequest extends FormRequest
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
            'phone' => 'required|string|min:9|max:9',
            'code' => 'required|integer|digits:4',
        ];
    }

    public function validationData(): array
    {
        $data = parent::validationData();

        $rawPhone = (string) ($data['phone'] ?? '');

        // Keep only digits
        $digitsOnlyPhone = preg_replace('/\D+/', '', $rawPhone) ?? '';

        // If phone starts with 05..., drop the leading 0 so 0512346789 == 512346789
        if (Str::startsWith($digitsOnlyPhone, '05')) {
            $digitsOnlyPhone = substr($digitsOnlyPhone, 1);
        }

        $data['phone'] = $digitsOnlyPhone;

        return $data;
    }


}
