<?php

namespace App\Http\Requests\TestStep;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_case_id' => ['sometimes', 'integer', 'exists:test_cases,id'],
            'step_number' => ['sometimes', 'integer', 'min:0'],
            'action' => ['nullable', 'string'],
            'expected' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:normal,alternativo,excepcion'],
        ];
    }
}