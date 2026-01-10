<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\TaskDependency\StoreTaskDependencyRequest;
use App\Http\Requests\TaskDependency\UpdateTaskDependencyRequest;
use App\Services\TaskDependencyService;
use Illuminate\Http\Request;

class TaskDependencyController extends ApiController
{
    public function __construct(private TaskDependencyService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'task_id' => $request->query('task_id'),
                'depends_on_task_id' => $request->query('depends_on_task_id'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function indexByTask(int $taskId)
    {
        try {
            $data = $this->service->list([
                'task_id' => $taskId,
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(StoreTaskDependencyRequest $request)
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

    public function update(UpdateTaskDependencyRequest $request, int $id)
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