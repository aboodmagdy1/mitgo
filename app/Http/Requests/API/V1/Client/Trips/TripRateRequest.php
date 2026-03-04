<?php

namespace App\Http\Requests\API\V1\Client\Trips;

use Illuminate\Foundation\Http\FormRequest;

class TripRateRequest extends FormRequest
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
            'rate' => 'required|integer|min:1|max:5',
            'rate_id' => 'nullable|exists:rating_comments,id',
        ];
    }
}
