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
            'label' => ['nullable', 'string', 'max:100'],
        ];
    }
}
