<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\TestCase\StoreTestCaseRequest;
use App\Http\Requests\TestCase\UpdateTestCaseRequest;
use App\Services\TestCaseService;
use Illuminate\Http\Request;

class TestCaseController extends ApiController
{
    public function __construct(private TestCaseService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->listWithRelations([
                'component_id' => $request->query('component_id'),
                'status' => $request->query('status'),
                'search' => $request->query('search'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(StoreTestCaseRequest $request)
    {
        try {
            $testCase = $this->service->create($request->validated());
            return $this->showAfterAction($testCase, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function indexWithSteps(Request $request)
    {
        try {
            $data = $this->service->listWithSteps([
                'component_id' => $request->query('component_id'),
                'status' => $request->query('status'),
                'search' => $request->query('search'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function indexByComponent(int $componentId)
    {
        try {
            $data = $this->service->listByComponent($componentId);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function addStep(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'action' => ['nullable', 'string'],
                'expected' => ['nullable', 'string'],
                'type' => ['nullable', 'string', 'in:normal,alternativo,excepcion'],
            ]);
            $testCase = $this->service->findOrFail($id);
            $step = $this->service->addStep($testCase, $data);
            return $this->showAfterAction($step, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function reorderSteps(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'orderedStepIds' => ['required', 'array'],
                'orderedStepIds.*' => ['integer'],
            ]);
            $testCase = $this->service->findOrFail($id);
            $this->service->reorderSteps($testCase, $data['orderedStepIds']);
            return $this->showMessage('Pasos reordenados con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function addActor(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'actor_name' => ['required', 'string', 'max:100'],
            ]);
            $testCase = $this->service->findOrFail($id);
            $actor = $this->service->addActor($testCase, $data['actor_name']);
            return $this->showAfterAction($actor, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function duplicate(int $id)
    {
        try {
            $testCase = $this->service->findOrFail($id);
            $copy = $this->service->duplicate($testCase);
            return $this->showAfterAction($copy, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function markStatus(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'status' => ['required', 'string', 'in:untested,passed,failed'],
            ]);
            $testCase = $this->service->findOrFail($id);
            $testCase = $this->service->markStatus($testCase, $data['status']);
            return $this->showAfterAction($testCase, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function bugs(int $id)
    {
        try {
            $testCase = $this->service->findOrFail($id);
            return $this->showAll($this->service->bugs($testCase));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $testCase = $this->service->findOrFail($id);
            return $this->showOne($testCase);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateTestCaseRequest $request, int $id)
    {
        try {
            $testCase = $this->service->findOrFail($id);
            $testCase = $this->service->update($testCase, $request->all());
            return $this->showAfterAction($testCase, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $testCase = $this->service->findOrFail($id);
            $this->service->delete($testCase);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}