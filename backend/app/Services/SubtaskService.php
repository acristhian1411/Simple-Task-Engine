<?php

namespace App\Services;

use App\Models\Subtask;
use Illuminate\Database\Eloquent\Collection;

class SubtaskService
{
    public function list(array $filters = []): Collection
    {
        $query = Subtask::query();
        if (isset($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }
        if (isset($filters['is_completed'])) {
            $query->where('is_completed', (bool) $filters['is_completed']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where('title', 'ILIKE', "%$s%");
        }
        return $query->latest()->get();
    }

    public function create(array $data): Subtask
    {
        return Subtask::create($data);
    }

    public function findOrFail(int $id): Subtask
    {
        return Subtask::findOrFail($id);
    }

    public function update(Subtask $subtask, array $data): Subtask
    {
        $subtask->update($data);
        return $subtask;
    }

    public function delete(Subtask $subtask): void
    {
        $subtask->delete();
    }
}
