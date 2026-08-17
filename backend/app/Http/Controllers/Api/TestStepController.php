<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\TestStep\StoreTestStepRequest;
use App\Http\Requests\TestStep\UpdateTestStepRequest;
use App\Services\TestStepService;
use Illuminate\Http\Request;

class TestStepController extends ApiController
{
    public function __construct(private TestStepService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'test_case_id' => $request->query('test_case_id'),
                'type' => $request->query('type'),
                'search' => $request->query('search'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function indexByTestCase(int $testCaseId)
    {
        try {
            $data = $this->service->list([
                'test_case_id' => $testCaseId,
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(StoreTestStepRequest $request)
    {
        try {
            $step = $this->service->create($request->validated());
            return $this->showAfterAction($step, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $step = $this->service->findOrFail($id);
            return $this->showOne($step);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateTestStepRequest $request, int $id)
    {
        try {
            $step = $this->service->findOrFail($id);
            $step = $this->service->update($step, $request->all());
            return $this->showAfterAction($step, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $step = $this->service->findOrFail($id);
            $this->service->delete($step);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}