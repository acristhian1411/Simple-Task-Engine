<?php

namespace App\Services;

use App\Models\Bugs;
use App\Models\Task;
use App\Models\Recordings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class BugService
{
    public function list(array $filters = []): Collection
    {
        $query = Bugs::query();
        if (isset($filters['test_case_id'])) {
            $query->where('test_case_id', $filters['test_case_id']);
        }
        if (isset($filters['test_step_id'])) {
            $query->where('test_step_id', $filters['test_step_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('title', 'ILIKE', "%$s%")
                    ->orWhere('description', 'ILIKE', "%$s%");
            });
        }
        return $query->latest()->get();
    }

    public function listWithRelations(array $filters = []): Collection
    {
        $query = Bugs::with('testCase.component', 'testStep', 'reportedBy');
        if (isset($filters['test_case_id'])) {
            $query->where('test_case_id', $filters['test_case_id']);
        }
        if (isset($filters['test_step_id'])) {
            $query->where('test_step_id', $filters['test_step_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('title', 'ILIKE', "%$s%")
                    ->orWhere('description', 'ILIKE', "%$s%");
            });
        }
        return $query->latest()->get();
    }

    public function create(array $data): Bugs
    {
        $data['reported_by_id'] = $data['reported_by_id'] ?? Auth::id();
        return Bugs::create($data);
    }

    public function findOrFail(int $id): Bugs
    {
        return Bugs::findOrFail($id);
    }

    public function update(Bugs $bug, array $data): Bugs
    {
        $bug->update($data);
        return $bug;
    }

    public function delete(Bugs $bug): void
    {
        $bug->delete();
    }

    public function listByStatus(string $status): Collection
    {
        return Bugs::where('status', $status)->latest()->get();
    }

    public function listBySeverity(string $severity): Collection
    {
        return Bugs::where('severity', $severity)->latest()->get();
    }

    public function changeStatus(Bugs $bug, string $status): Bugs
    {
        $transitions = [
            'open' => ['in_progress', 'resolved', 'closed'],
            'in_progress' => ['resolved', 'closed'],
            'resolved' => ['closed'],
            'closed' => ['open'],
        ];

        $from = $bug->status;
        if ($from === $status) {
            return $bug;
        }
        if (!isset($transitions[$from]) || !in_array($status, $transitions[$from], true)) {
            throw new \InvalidArgumentException("Transición de estado no permitida: {$from} → {$status}");
        }
        $bug->update(['status' => $status]);
        return $bug;
    }

    public function linkTask(Bugs $bug, Task $task, string $relationType = 'related'): void
    {
        if (!in_array($relationType, ['fixes', 'blocked_by', 'related'], true)) {
            throw new \InvalidArgumentException('Tipo de relación inválido.');
        }
        $bug->tasks()->syncWithoutDetaching([
            $task->id => ['relation_type' => $relationType],
        ]);
    }

    public function unlinkTask(Bugs $bug, Task $task): void
    {
        $bug->tasks()->detach($task->id);
    }

    public function tasks(Bugs $bug): Collection
    {
        return $bug->tasks()->get();
    }

    public function attachRecording(Bugs $bug, Recordings $recording): Recordings
    {
        $recording->recordable()->associate($bug);
        $recording->save();
        return $recording;
    }
}