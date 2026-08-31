<?php

namespace App\Services;

use App\Models\Bugs;
use App\Models\Components;
use App\Models\TestCases;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function dashboard(): array
    {
        $totalModules = Components::where('type', '=', 'module')->count();
        $totalTestCases = TestCases::count();
        $totalBugs = Bugs::count();
        $openBugs = Bugs::where('status', '!=', 'closed')->count();

        $bugsBySeverity = Bugs::query()
            ->whereNotNull('severity')
            ->select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->map(fn($value) => (int) $value)
            ->all();

        $bugsByStatus = Bugs::query()
            ->whereNotNull('status')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn($value) => (int) $value)
            ->all();

        $testCasesByStatus = TestCases::query()
            ->whereNotNull('status')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn($value) => (int) $value)
            ->all();

        $recentBugs = Bugs::with('testCase.component')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn(Bugs $bug) => [
                'id' => $bug->id,
                'title' => $bug->title,
                'moduleName' => $bug->testCase?->component?->name,
                'createdAt' => $bug->created_at,
                'severity' => $bug->severity,
                'status' => $bug->status,
            ])
            ->all();

        $modules = Components::orderBy('name')
            ->where('type', '=', 'module')
            ->get(['id', 'name', 'description'])
            ->map(fn(Components $component) => [
                'id' => $component->id,
                'name' => $component->name,
                'description' => $component->description,
            ])
            ->all();

        return [
            'stats' => [
                'totalModules' => $totalModules,
                'totalTestCases' => $totalTestCases,
                'totalBugs' => $totalBugs,
                'openBugs' => $openBugs,
            ],
            'bugsBySeverity' => $bugsBySeverity,
            'bugsByStatus' => $bugsByStatus,
            'testCasesByStatus' => $testCasesByStatus,
            'recentBugs' => $recentBugs,
            'modules' => $modules,
        ];
    }
}
