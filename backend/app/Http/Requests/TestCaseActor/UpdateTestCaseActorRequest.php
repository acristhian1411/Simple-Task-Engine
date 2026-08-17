<?php

namespace App\Http\Requests\TestCaseActor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestCaseActorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_case_id' => ['sometimes', 'integer', 'exists:test_cases,id'],
            'actor_name' => ['sometimes', 'string', 'max:100'],
        ];
    }
}