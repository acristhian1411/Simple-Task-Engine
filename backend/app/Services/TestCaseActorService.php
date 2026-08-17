<?php

namespace App\Services;

use App\Models\TestCaseActors;
use Illuminate\Database\Eloquent\Collection;

class TestCaseActorService
{
    public function list(array $filters = []): Collection
    {
        $query = TestCaseActors::query();
        if (isset($filters['test_case_id'])) {
            $query->where('test_case_id', $filters['test_case_id']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('actor_name', 'ILIKE', "%$s%");
            });
        }
        return $query->latest()->get();
    }

    public function create(array $data): TestCaseActors
    {
        return TestCaseActors::create($data);
    }

    public function findOrFail(int $id): TestCaseActors
    {
        return TestCaseActors::findOrFail($id);
    }

    public function update(TestCaseActors $actor, array $data): TestCaseActors
    {
        $actor->update($data);
        return $actor;
    }

    public function delete(TestCaseActors $actor): void
    {
        $actor->delete();
    }
}