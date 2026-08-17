<?php

namespace App\Http\Requests\ExtentionToken;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExtentionTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['nullable', 'string'],
            'token_hash' => ['sometimes', 'required', 'string', 'max:64'],
            'label' => ['nullable', 'string', 'max:100'],
            'last_used_at' => ['nullable', 'date'],
            'revoked_at' => ['nullable', 'date'],
        ];
    }
}