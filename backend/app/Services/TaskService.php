<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    public function list(array $filters = []): Collection
    {
        $query = Task::query();
        if (isset($filters['list_id'])) {
            $query->where('list_id', $filters['list_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search){
                $q->where('title', 'ILIKE', "%$search%")
                  ->orWhere('description', 'ILIKE', "%$search%");
            });
        }
        return $query->latest()->get();
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function findOrFail(int $id): Task
    {
        return Task::findOrFail($id);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        return $task;
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
