<?php

namespace App\Services;

use App\Models\ListModel;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(private BoardAccessService $access) {}

    public function list(array $filters = []): Collection
    {
        $query = Task::with(Task::ASSIGNEE_RELATION);
        if (isset($filters['list_id'])) {
            $query->where('list_id', $filters['list_id']);
        }
        if (isset($filters['user_id'])) {
            $query->whereHas(
                'list.board',
                fn ($q) => $this->access->scopeAccessible($q, (int) $filters['user_id'])
            );
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
        $data = $this->applyAssignee($data, (int) $data['list_id']);
        $task = Task::create($data);
        return $task->load(Task::ASSIGNEE_RELATION);
    }

    public function findOrFail(int $id): Task
    {
        return Task::with(Task::ASSIGNEE_RELATION)->findOrFail($id);
    }

    public function update(Task $task, array $data): Task
    {
        if ($this->isDoneStatus($data['status'] ?? null) && !$this->isDoneStatus($task->status)) {
            $this->assertCompletable($task);
        }

        $data = $this->applyAssignee($data, (int) ($data['list_id'] ?? $task->list_id), $task);
        $task->update($data);
        return $task->load(Task::ASSIGNEE_RELATION);
    }

    /**
     * Traduce el "assignee_id" de la API a la columna donde se guarda el responsable
     * y valida que sea miembro del tablero. Junto con unassignFromBoard es lo único
     * que conoce la columna: si se pasa a varios responsables, se cambia acá.
     */
    private function applyAssignee(array $data, int $listId, ?Task $task = null): array
    {
        if (array_key_exists('assignee_id', $data)) {
            $assigneeId = $data['assignee_id'] === null ? null : (int) $data['assignee_id'];
            unset($data['assignee_id']);
            $data['assigned_to'] = $assigneeId;
        } elseif ($task !== null && $listId !== (int) $task->list_id) {
            // Si la tarea cambia de lista, el responsable actual tiene que tener acceso al destino
            $assigneeId = $task->assigned_to;
        } else {
            return $data;
        }

        if ($assigneeId !== null) {
            $this->access->assertAssignable($listId, $assigneeId);
        }

        return $data;
    }

    /**
     * Deja sin responsable las tareas del tablero asignadas al usuario
     * (incluidas las eliminadas, para que no vuelvan asignadas si se restauran).
     */
    public function unassignFromBoard(int $boardId, int $userId): void
    {
        Task::withTrashed()
            ->where('assigned_to', $userId)
            ->whereIn('list_id', ListModel::withTrashed()->where('board_id', $boardId)->select('id'))
            ->update(['assigned_to' => null]);
    }

    /**
     * Rechaza la transición a "done" si existen dependencias pendientes.
     */
    public function assertCompletable(Task $task): void
    {
        $blockers = $this->blockedBy($task);

        if ($blockers->isNotEmpty()) {
            $names = $blockers->pluck('title')->implode('", "');

            throw ValidationException::withMessages([
                'status' => ["No se puede completar la tarea: depende de \"{$names}\"."],
            ]);
        }
    }

    protected function isDoneStatus(?string $status): bool
    {
        return $status !== null && strtolower(trim($status)) === 'done';
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
