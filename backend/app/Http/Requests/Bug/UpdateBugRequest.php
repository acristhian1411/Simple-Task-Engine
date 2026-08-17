<?php

namespace App\Http\Requests\Bug;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'required', 'string'],
            'severity' => ['sometimes', 'required', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'test_case_id' => ['nullable', 'integer', 'exists:test_cases,id'],
            'test_step_id' => ['nullable', 'integer', 'exists:test_steps,id'],
        ];
    }
}