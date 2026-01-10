<?php

namespace App\Services;

use App\Models\TaskDependency;
use Illuminate\Database\Eloquent\Collection;

class TaskDependencyService
{
    public function list(array $filters = []): Collection
    {
        $query = TaskDependency::query();
        if (isset($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }
        if (isset($filters['depends_on_task_id'])) {
            $query->where('depends_on_task_id', $filters['depends_on_task_id']);
        }
        return $query->latest()->get();
    }

    public function create(array $data): TaskDependency
    {
        return TaskDependency::create($data);
    }

    public function findOrFail(int $id): TaskDependency
    {
        return TaskDependency::findOrFail($id);
    }

    public function update(TaskDependency $dep, array $data): TaskDependency
    {
        $dep->update($data);
        return $dep;
    }

    public function delete(TaskDependency $dep): void
    {
        $dep->delete();
    }
}
