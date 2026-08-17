<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Component\StoreComponentRequest;
use App\Http\Requests\Component\UpdateComponentRequest;
use App\Models\Components;
use App\Services\ComponentService;
use Illuminate\Http\Request;

class ComponentController extends ApiController
{
    public function __construct(private ComponentService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->listWithRelations([
                'type' => $request->query('type'),
                'parent_id' => $request->query('parent_id'),
                'search' => $request->query('search'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function indexByParent(int $parentId)
    {
        try {
            $data = $this->service->list([
                'parent_id' => $parentId,
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(StoreComponentRequest $request)
    {
        try {
            $component = $this->service->create($request->validated());
            return $this->showAfterAction($component, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $component = $this->service->findOrFail($id);
            return $this->showOne($component);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateComponentRequest $request, int $id)
    {
        try {
            $component = $this->service->findOrFail($id);
            $component = $this->service->update($component, $request->all());
            return $this->showAfterAction($component, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $component = $this->service->findOrFail($id);
            $this->service->delete($component);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function tree(Request $request)
    {
        try {
            $data = $this->service->tree([
                'type' => $request->query('type'),
                'search' => $request->query('search'),
            ]);
            return response()->json(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function dependencies(int $id)
    {
        try {
            $component = $this->service->findOrFail($id);
            return $this->showAll($this->service->dependencies($component));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function dependents(int $id)
    {
        try {
            $component = $this->service->findOrFail($id);
            return $this->showAll($this->service->dependents($component));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function criticalDependents(int $id)
    {
        try {
            $component = $this->service->findOrFail($id);
            return $this->showAll($this->service->criticalDependents($component));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function attachDependency(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'depends_on_id' => ['required', 'integer', 'exists:components,id'],
                'criticality' => ['nullable', 'string', 'in:critical,optional'],
            ]);
            $component = $this->service->findOrFail($id);
            $dependsOn = Components::findOrFail($data['depends_on_id']);
            $dep = $this->service->attachDependency($component, $dependsOn, $data['criticality'] ?? 'optional');
            return $this->showAfterAction($dep, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function detachDependency(int $id, int $dependsOnId)
    {
        try {
            $component = $this->service->findOrFail($id);
            $dependsOn = Components::findOrFail($dependsOnId);
            $this->service->detachDependency($component, $dependsOn);
            return $this->showMessage('Dependencia eliminada con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function tasks(int $id)
    {
        try {
            $component = $this->service->findOrFail($id);
            return $this->showAll($this->service->tasks($component));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function testCases(int $id)
    {
        try {
            $component = $this->service->findOrFail($id);
            return $this->showAll($this->service->testCases($component));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}