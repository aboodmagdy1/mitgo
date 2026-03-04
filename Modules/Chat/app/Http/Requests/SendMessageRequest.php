<?php

namespace Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'receiver_id' => 'required_without:conversation_id|nullable|integer|exists:users,id',
            'conversation_id' => 'required_without:receiver_id|nullable|integer|exists:conversations,id',
            'content' => 'required',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'receiver_id.required_without' => 'Either receiver_id or conversation_id is required',
            'conversation_id.required_without' => 'Either receiver_id or conversation_id is required',
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
