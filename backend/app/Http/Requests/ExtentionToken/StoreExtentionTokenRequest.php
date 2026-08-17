<?php

namespace App\Http\Requests\ExtentionToken;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtentionTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['nullable', 'string'],
            'token_hash' => ['required', 'string', 'max:64', 'unique:extension_tokens,token_hash'],
            'label' => ['nullable', 'string', 'max:100'],
            'last_used_at' => ['nullable', 'date'],
            'revoked_at' => ['nullable', 'date'],
        ];
    }
}