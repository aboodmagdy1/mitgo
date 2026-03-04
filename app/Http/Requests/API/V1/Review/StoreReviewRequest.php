<?php

namespace App\Http\Requests\API\V1\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreReviewRequest extends FormRequest
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
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'provider_id.required' => 'Provider ID is required',
            'provider_id.exists' => 'Selected provider does not exist',
            'rating.required' => 'Rating is required',
            'rating.min' => 'Rating must be at least 1',
            'rating.max' => 'Rating cannot exceed 5',
            'comment.max' => 'Comment cannot exceed 1000 characters',
        ];
    }


}
