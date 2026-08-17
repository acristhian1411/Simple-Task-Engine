<?php

namespace App\Http\Requests\ComponentDependency;

use Illuminate\Foundation\Http\FormRequest;

class StoreComponentDependencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'component_id' => ['required', 'integer', 'exists:components,id', 'different:depends_on_id'],
            'depends_on_id' => ['required', 'integer', 'exists:components,id'],
            'criticality' => ['nullable', 'string', 'in:critical,optional'],
        ];
    }
}