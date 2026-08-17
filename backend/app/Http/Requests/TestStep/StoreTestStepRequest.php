<?php

namespace App\Http\Requests\TestStep;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_case_id' => ['required', 'integer', 'exists:test_cases,id'],
            'step_number' => ['required', 'integer', 'min:0'],
            'action' => ['nullable', 'string'],
            'expected' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:normal,alternativo,excepcion'],
        ];
    }
}