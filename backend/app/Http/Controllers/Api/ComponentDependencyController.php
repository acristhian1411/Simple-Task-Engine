<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ComponentDependency\StoreComponentDependencyRequest;
use App\Http\Requests\ComponentDependency\UpdateComponentDependencyRequest;
use App\Services\ComponentDependencyService;
use Illuminate\Http\Request;

class ComponentDependencyController extends ApiController
{
    public function __construct(private ComponentDependencyService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->listWithRelations([
                'component_id' => $request->query('component_id'),
                'depends_on_id' => $request->query('depends_on_id'),
                'criticality' => $request->query('criticality'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function indexByComponent(int $componentId)
    {
        try {
            $data = $this->service->list([
                'component_id' => $componentId,
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(StoreComponentDependencyRequest $request)
    {
        try {
            $dep = $this->service->create($request->validated());
            return $this->showAfterAction($dep, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $dep = $this->service->findOrFail($id);
            return $this->showOne($dep);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateComponentDependencyRequest $request, int $id)
    {
        try {
            $dep = $this->service->findOrFail($id);
            $dep = $this->service->update($dep, $request->all());
            return $this->showAfterAction($dep, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $dep = $this->service->findOrFail($id);
            $this->service->delete($dep);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}