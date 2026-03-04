<?php

namespace App\Http\Requests\API\V1\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'city_id' => 'required|integer|exists:cities,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'age'=>'required|integer',
            'gender'=>'required|in:female,male',
            'email' => [
                'nullable',
                'email',
                'unique:users,email'
              
            ]
        ];
    }


    
}
