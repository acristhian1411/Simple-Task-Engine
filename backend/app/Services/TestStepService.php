<?php

namespace App\Services;

use App\Models\TestSteps;
use Illuminate\Database\Eloquent\Collection;

class TestStepService
{
    public function list(array $filters = []): Collection
    {
        $query = TestSteps::query();
        if (isset($filters['test_case_id'])) {
            $query->where('test_case_id', $filters['test_case_id']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('action', 'ILIKE', "%$s%")
                    ->orWhere('expected', 'ILIKE', "%$s%");
            });
        }
        return $query->orderBy('step_number')->get();
    }

    public function create(array $data): TestSteps
    {
        return TestSteps::create($data);
    }

    public function findOrFail(int $id): TestSteps
    {
        return TestSteps::findOrFail($id);
    }

    public function update(TestSteps $step, array $data): TestSteps
    {
        $step->update($data);
        return $step;
    }

    public function delete(TestSteps $step): void
    {
        $step->delete();
    }
}