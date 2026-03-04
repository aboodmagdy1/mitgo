<?php

namespace App\Http\Requests\API\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedLocationRequest extends FormRequest
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
            'address' => 'required|string|max:255',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'name' => 'required|string|max:255',
        ];
    }
}
