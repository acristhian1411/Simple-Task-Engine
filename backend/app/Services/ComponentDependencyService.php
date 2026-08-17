<?php

namespace App\Services;

use App\Models\ComponentDependencies;
use Illuminate\Database\Eloquent\Collection;

class ComponentDependencyService
{
    public function list(array $filters = []): Collection
    {
        $query = ComponentDependencies::query();
        if (isset($filters['component_id'])) {
            $query->where('component_id', $filters['component_id']);
        }
        if (isset($filters['depends_on_id'])) {
            $query->where('depends_on_id', $filters['depends_on_id']);
        }
        if (isset($filters['criticality'])) {
            $query->where('criticality', $filters['criticality']);
        }
        return $query->latest()->get();
    }

    public function listWithRelations(array $filters = []): Collection
    {
        $query = ComponentDependencies::with('component', 'dependsOn');
        if (isset($filters['component_id'])) {
            $query->where('component_id', $filters['component_id']);
        }
        if (isset($filters['depends_on_id'])) {
            $query->where('depends_on_id', $filters['depends_on_id']);
        }
        if (isset($filters['criticality'])) {
            $query->where('criticality', $filters['criticality']);
        }
        return $query->latest()->get();
    }

    public function create(array $data): ComponentDependencies
    {
        return ComponentDependencies::create($data);
    }

    public function findOrFail(int $id): ComponentDependencies
    {
        return ComponentDependencies::findOrFail($id);
    }

    public function update(ComponentDependencies $dep, array $data): ComponentDependencies
    {
        $dep->update($data);
        return $dep;
    }

    public function delete(ComponentDependencies $dep): void
    {
        $dep->delete();
    }
}