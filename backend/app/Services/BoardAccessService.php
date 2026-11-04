<?php

namespace App\Services;

use App\Models\Board;
use App\Models\ListModel;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centraliza el control de acceso a los recursos que cuelgan de un tablero.
 * Un usuario accede si es dueño del tablero o si figura en board_user.
 */
class BoardAccessService
{
    /**
     * Restringe una consulta de boards a los propios o compartidos con el usuario.
     */
    public function scopeAccessible(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhereHas('members', fn (Builder $members) => $members->where('users.id', $userId));
        });
    }

    public function accessibleBoards(int $userId): Builder
    {
        return $this->scopeAccessible(Board::query(), $userId);
    }

    public function assertBoard(int $boardId, int $userId): void
    {
        $this->accessibleBoards($userId)->whereKey($boardId)->firstOrFail();
    }

    public function assertList(int $listId, int $userId): void
    {
        ListModel::whereKey($listId)
            ->whereHas('board', fn (Builder $q) => $this->scopeAccessible($q, $userId))
            ->firstOrFail();
    }

    public function assertTask(int $taskId, int $userId): void
    {
        Task::whereKey($taskId)
            ->whereHas('list.board', fn (Builder $q) => $this->scopeAccessible($q, $userId))
            ->firstOrFail();
    }

    public function assertSubtask(int $subtaskId, int $userId): void
    {
        $subtask = Subtask::findOrFail($subtaskId);
        $this->assertTask($subtask->task_id, $userId);
    }

    public function assertDependency(int $dependencyId, int $userId): void
    {
        $dependency = TaskDependency::findOrFail($dependencyId);
        $this->assertTask($dependency->task_id, $userId);
        $this->assertTask($dependency->depends_on_task_id, $userId);
    }

    /**
     * Valida el recurso asociado a un comentario. Solo las tareas son
     * recursos de tablero; el resto de los tipos son catálogos globales.
     */
    public function assertCommentable(?string $type, int $id, int $userId): void
    {
        if ($type && is_a($type, Task::class, true)) {
            $this->assertTask($id, $userId);
        }
    }
}
