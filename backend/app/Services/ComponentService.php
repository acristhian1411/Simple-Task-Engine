<?php

namespace App\Services;

use App\Models\Components;
use App\Models\ComponentDependencies;
use Illuminate\Database\Eloquent\Collection;

class ComponentService
{
    public function list(array $filters = []): Collection
    {
        $query = Components::query();
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ILIKE', "%$s%")
                    ->orWhere('description', 'ILIKE', "%$s%");
            });
        }
        return $query->latest()->get();
    }

    public function listWithRelations(array $filters = []): Collection
    {
        $query = Components::with('parent');
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ILIKE', "%$s%")
                    ->orWhere('description', 'ILIKE', "%$s%");
            });
        }
        return $query->latest()->get();
    }

    public function create(array $data): Components
    {
        return Components::create($data);
    }

    public function findOrFail(int $id): Components
    {
        return Components::findOrFail($id);
    }

    public function update(Components $component, array $data): Components
    {
        $component->update($data);
        return $component;
    }

    public function delete(Components $component): void
    {
        $component->delete();
    }

    public function tree(array $filters = []): array
    {
        $query = Components::query();
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ILIKE', "%$s%")
                    ->orWhere('description', 'ILIKE', "%$s%");
            });
        }
        $components = $query->get();
        $grouped = $components->groupBy('parent_id');

        $build = function ($parentId = null) use (&$build, $grouped) {
            $nodes = [];
            foreach ($grouped->get($parentId, collect()) as $item) {
                $node = $item->toArray();
                $node['children'] = $build($item->id);
                $nodes[] = $node;
            }
            return $nodes;
        };

        return $build(null);
    }

    public function dependencies(Components $component): Collection
    {
        return $component->dependencies()->get();
    }

    public function dependents(Components $component): Collection
    {
        return $component->dependents()->get();
    }

    public function criticalDependents(Components $component): Collection
    {
        return $component->dependents()
            ->wherePivot('criticality', 'critical')
            ->get();
    }

    public function attachDependency(Components $component, Components $dependsOn, string $criticality = 'optional'): ComponentDependencies
    {
        if ($component->id === $dependsOn->id) {
            throw new \InvalidArgumentException('Un componente no puede depender de sí mismo.');
        }
        if ($this->hasCycle($component->id, $dependsOn->id)) {
            throw new \InvalidArgumentException('La dependencia generaría un ciclo en el grafo.');
        }
        $exists = ComponentDependencies::where('component_id', $component->id)
            ->where('depends_on_id', $dependsOn->id)
            ->exists();
        if ($exists) {
            throw new \InvalidArgumentException('La dependencia ya existe.');
        }
        return ComponentDependencies::create([
            'component_id' => $component->id,
            'depends_on_id' => $dependsOn->id,
            'criticality' => in_array($criticality, ['critical', 'optional'], true) ? $criticality : 'optional',
        ]);
    }

    public function detachDependency(Components $component, Components $dependsOn): void
    {
        ComponentDependencies::where('component_id', $component->id)
            ->where('depends_on_id', $dependsOn->id)
            ->delete();
    }

    public function hasCycle(int $componentId, int $dependsOnId): bool
    {
        $adjacency = [];
        foreach (ComponentDependencies::select('component_id', 'depends_on_id')->get() as $dep) {
            $adjacency[$dep->component_id][] = $dep->depends_on_id;
        }

        $visited = [$dependsOnId => true];
        $stack = [$dependsOnId];

        while (!empty($stack)) {
            $current = array_pop($stack);
            foreach ($adjacency[$current] ?? [] as $next) {
                if ($next === $componentId) {
                    return true;
                }
                if (!isset($visited[$next])) {
                    $visited[$next] = true;
                    $stack[] = $next;
                }
            }
        }

        return false;
    }

    public function tasks(Components $component): Collection
    {
        return $component->tasks()->get();
    }

    public function testCases(Components $component): Collection
    {
        return $component->testCases()->get();
    }
}
