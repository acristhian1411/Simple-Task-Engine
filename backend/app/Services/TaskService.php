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

    public function attachComponent(Task $task, int $componentId): void
    {
        $task->components()->syncWithoutDetaching([$componentId]);
    }

    public function detachComponent(Task $task, int $componentId): void
    {
        $task->components()->detach($componentId);
    }

    public function syncComponents(Task $task, array $componentIds): void
    {
        $task->components()->sync($componentIds);
    }

    public function attachBug(Task $task, int $bugId, string $relationType = 'related'): void
    {
        if (!in_array($relationType, ['fixes', 'blocked_by', 'related'], true)) {
            throw new \InvalidArgumentException('Tipo de relación inválido.');
        }
        $task->bugs()->syncWithoutDetaching([
            $bugId => ['relation_type' => $relationType],
        ]);
    }

    public function detachBug(Task $task, int $bugId): void
    {
        $task->bugs()->detach($bugId);
    }

    public function syncBugs(Task $task, array $bugIds): void
    {
        $current = $task->bugs()->get();
        $sync = [];
        foreach ($current as $bug) {
            $sync[$bug->id] = ['relation_type' => $bug->pivot->relation_type];
        }
        foreach ($bugIds as $bugId) {
            $bugId = (int) $bugId;
            if (!isset($sync[$bugId])) {
                $sync[$bugId] = ['relation_type' => 'related'];
            }
        }
        $task->bugs()->sync($sync);
    }

    public function blockedBy(Task $task): Collection
    {
        return Task::whereIn('id', function ($query) use ($task) {
            $query->select('depends_on_task_id')
                ->from('task_dependencies')
                ->where('task_id', $task->id)
                ->whereNull('deleted_at');
        })
            ->where('status', '!=', 'done')
            ->get();
    }
}
