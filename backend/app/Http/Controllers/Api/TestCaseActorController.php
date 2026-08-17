<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\TestCaseActor\StoreTestCaseActorRequest;
use App\Http\Requests\TestCaseActor\UpdateTestCaseActorRequest;
use App\Services\TestCaseActorService;
use Illuminate\Http\Request;

class TestCaseActorController extends ApiController
{
    public function __construct(private TestCaseActorService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'test_case_id' => $request->query('test_case_id'),
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

    public function store(StoreTestCaseActorRequest $request)
    {
        try {
            $actor = $this->service->create($request->validated());
            return $this->showAfterAction($actor, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $actor = $this->service->findOrFail($id);
            return $this->showOne($actor);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateTestCaseActorRequest $request, int $id)
    {
        try {
            $actor = $this->service->findOrFail($id);
            $actor = $this->service->update($actor, $request->all());
            return $this->showAfterAction($actor, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $actor = $this->service->findOrFail($id);
            $this->service->delete($actor);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}