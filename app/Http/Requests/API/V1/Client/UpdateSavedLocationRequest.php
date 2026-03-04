<?php

namespace App\Http\Requests\API\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSavedLocationRequest extends FormRequest
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
            'address' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'name' => 'nullable|string|max:255',
        ];
    }
}
