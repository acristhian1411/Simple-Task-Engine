<?php

namespace App\Services;

use App\Models\TestCases;
use App\Models\TestSteps;
use App\Models\TestCaseActors;
use Illuminate\Database\Eloquent\Collection;

class TestCaseService
{
    public function list(array $filters = []): Collection
    {
        $query = TestCases::query();
        if (isset($filters['component_id'])) {
            $query->where('component_id', $filters['component_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
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
        $query = TestCases::with('component');
        if (isset($filters['component_id'])) {
            $query->where('component_id', $filters['component_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
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

    public function create(array $data): TestCases
    {
        return TestCases::create($data);
    }

    public function findOrFail(int $id): TestCases
    {
        return TestCases::findOrFail($id);
    }

    public function update(TestCases $testCase, array $data): TestCases
    {
        $testCase->update($data);
        return $testCase;
    }

    public function delete(TestCases $testCase): void
    {
        $testCase->delete();
    }

    public function listWithSteps(array $filters = []): Collection
    {
        $query = TestCases::with(['testSteps' => function ($q) {
            $q->orderBy('step_number');
        }]);
        if (isset($filters['component_id'])) {
            $query->where('component_id', $filters['component_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
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

    public function listByComponent(int $componentId): Collection
    {
        return $this->list(['component_id' => $componentId]);
    }

    public function addStep(TestCases $testCase, array $data): TestSteps
    {
        $nextNumber = (TestSteps::where('test_case_id', $testCase->id)->max('step_number') ?? 0) + 1;
        return TestSteps::create(array_merge($data, [
            'test_case_id' => $testCase->id,
            'step_number' => $nextNumber,
        ]));
    }

    public function reorderSteps(TestCases $testCase, array $orderedStepIds): void
    {
        foreach ($orderedStepIds as $position => $stepId) {
            TestSteps::where('id', $stepId)
                ->where('test_case_id', $testCase->id)
                ->update(['step_number' => $position + 1]);
        }
    }

    public function addActor(TestCases $testCase, string $actorName): TestCaseActors
    {
        return TestCaseActors::create([
            'test_case_id' => $testCase->id,
            'actor_name' => $actorName,
        ]);
    }

    public function duplicate(TestCases $testCase): TestCases
    {
        $copy = $testCase->replicate();
        $copy->title = $testCase->title . ' (copia)';
        $copy->status = 'untested';
        $copy->save();

        foreach ($testCase->testSteps()->orderBy('step_number')->get() as $step) {
            $stepCopy = $step->replicate();
            $stepCopy->test_case_id = $copy->id;
            $stepCopy->save();
        }

        foreach ($testCase->actors()->get() as $actor) {
            $actorCopy = $actor->replicate();
            $actorCopy->test_case_id = $copy->id;
            $actorCopy->save();
        }

        return $copy->load('testSteps', 'actors');
    }

    public function markStatus(TestCases $testCase, string $status): TestCases
    {
        $testCase->update(['status' => $status]);
        return $testCase;
    }

    public function bugs(TestCases $testCase): Collection
    {
        return $testCase->bugs()->get();
    }
}