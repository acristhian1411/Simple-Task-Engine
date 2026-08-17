<?php

namespace App\Http\Requests\TestCaseActor;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestCaseActorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_case_id' => ['required', 'integer', 'exists:test_cases,id'],
            'actor_name' => ['required', 'string', 'max:100'],
        ];
    }
}