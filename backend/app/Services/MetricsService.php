<?php

namespace App\Services;

use App\Models\Bugs;
use App\Models\Components;
use App\Models\Task;
use App\Models\ComponentDependencies;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    public function tasksByComponent(): array
    {
        return Components::query()
            ->withCount(['tasks' => function ($q) {
                $q->whereNull('tasks.deleted_at')
                    ->where('tasks.status', '!=', 'done');
            }])
            ->orderByDesc('tasks_count')
            ->get()
            ->map(fn (Components $component) => [
                'id' => $component->id,
                'name' => $component->name,
                'type' => $component->type,
                'total' => $component->tasks_count,
            ])
            ->values()
            ->all();
    }

    public function bugsBySeverity(): array
    {
        return DB::table('bugs')
            ->whereNull('deleted_at')
            ->where('status', '!=', 'closed')
            ->select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'severity' => $row->severity,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    public function blockedTasks(): array
    {
        return Task::with('dependencies.dependsOn')
            ->whereHas('dependencies.dependsOn', function ($q) {
                $q->where('status', '!=', 'done');
            })
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'blocked_by' => $task->dependencies
                    ->map(fn ($dep) => [
                        'id' => $dep->dependsOn->id ?? null,
                        'title' => $dep->dependsOn->title ?? null,
                        'status' => $dep->dependsOn->status ?? null,
                    ])
                    ->filter(fn ($d) => $d['id'] !== null && $d['status'] !== 'done')
                    ->values(),
            ])
            ->all();
    }

    public function componentsWithUnresolvedCriticalDeps(): array
    {
        $deps = ComponentDependencies::where('criticality', 'critical')
            ->with('component', 'dependsOn')
            ->get()
            ->filter(function (ComponentDependencies $dep) {
                return $dep->dependsOn === null || $dep->dependsOn->trashed();
            });

        return $deps
            ->groupBy('component_id')
            ->map(function ($group, $componentId) {
                $component = $group->first()->component;
                return [
                    'id' => $componentId,
                    'name' => $component->name ?? null,
                    'unresolved' => $group
                        ->map(fn (ComponentDependencies $dep) => [
                            'dependency_id' => $dep->depends_on_id,
                            'dependency_name' => $dep->dependsOn->name ?? '[eliminado]',
                        ])
                        ->values(),
                ];
            })
            ->values()
            ->all();
    }

    public function activityByUser(array $range): array
    {
        $query = DB::table('audits')
            ->join('users', 'users.id', '=', 'audits.user_id')
            ->whereNotNull('audits.user_id');

        $from = $range['from'] ?? null;
        $to = $range['to'] ?? null;
        if ($from) {
            $query->where('audits.created_at', '>=', Carbon::parse($from));
        }
        if ($to) {
            $query->where('audits.created_at', '<=', Carbon::parse($to));
        }

        return $query->select(
            'users.id as user_id',
            'users.name as user_name',
            'users.email as user_email',
            DB::raw('count(*) as total')
        )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->user_id,
                'user_name' => $row->user_name,
                'user_email' => $row->user_email,
                'total' => (int) $row->total,
            ])
            ->all();
    }
}