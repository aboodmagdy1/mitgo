<?php

namespace Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'receiver_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
