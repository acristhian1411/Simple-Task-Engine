<?php

namespace App\Services;

use App\Models\TaskDependency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

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
        $this->assertNoCycle((int) $data['task_id'], (int) $data['depends_on_task_id']);

        return TaskDependency::create($data);
    }

    /**
     * Rechaza dependencias que generen un ciclo en el grafo de tareas.
     * Al crear la arista `taskId -> dependsOnId`, se recorre el grafo desde
     * `dependsOnId` siguiendo sus dependencias: si se alcanza `taskId`,
     * agregar la arista cerraría un ciclo.
     */
    public function assertNoCycle(int $taskId, int $dependsOnId): void
    {
        if ($taskId === $dependsOnId) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => ['Una tarea no puede depender de sí misma.'],
            ]);
        }

        $adjacency = [];
        foreach (TaskDependency::select('task_id', 'depends_on_task_id')->get() as $dep) {
            $adjacency[$dep->task_id][] = $dep->depends_on_task_id;
        }

        $visited = [$dependsOnId => true];
        $stack = [$dependsOnId];

        while (!empty($stack)) {
            $current = array_pop($stack);
            foreach ($adjacency[$current] ?? [] as $next) {
                if ($next === $taskId) {
                    throw ValidationException::withMessages([
                        'depends_on_task_id' => ['La dependencia generaría un ciclo en el grafo de tareas.'],
                    ]);
                }
                if (!isset($visited[$next])) {
                    $visited[$next] = true;
                    $stack[] = $next;
                }
            }
        }
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
