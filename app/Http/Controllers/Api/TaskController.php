<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Services\TaskService;
use Illuminate\Http\Request;

class TaskController extends ApiController
{
    public function __construct(private TaskService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'list_id' => $request->query('list_id'),
                'status' => $request->query('status'),
                'search' => $request->query('search'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(StoreTaskRequest $request)
    {
        try {
            $task = $this->service->create($request->validated());
            return $this->showAfterAction($task, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $task = $this->service->findOrFail($id);
            return $this->showOne($task);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateTaskRequest $request, int $id)
    {
        try {
            $task = $this->service->findOrFail($id);
            $task = $this->service->update($task, $request->all());
            return $this->showAfterAction($task, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $task = $this->service->findOrFail($id);
            $this->service->delete($task);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}
