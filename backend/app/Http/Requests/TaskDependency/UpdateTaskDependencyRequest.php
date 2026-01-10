<?php

namespace App\Http\Requests\TaskDependency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskDependencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_id' => ['sometimes', 'integer', 'exists:tasks,id', 'different:depends_on_task_id'],
            'depends_on_task_id' => ['sometimes', 'integer', 'exists:tasks,id', 'different:task_id'],
        ];
    }
}
