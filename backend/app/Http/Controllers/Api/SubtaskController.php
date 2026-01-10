<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Subtask\StoreSubtaskRequest;
use App\Http\Requests\Subtask\UpdateSubtaskRequest;
use App\Services\SubtaskService;
use Illuminate\Http\Request;

class SubtaskController extends ApiController
{
    public function __construct(private SubtaskService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'task_id' => $request->query('task_id'),
                'is_completed' => $request->query('is_completed'),
                'search' => $request->query('search'),
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

    public function store(StoreSubtaskRequest $request)
    {
        try {
            $subtask = $this->service->create($request->validated());
            return $this->showAfterAction($subtask, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $subtask = $this->service->findOrFail($id);
            return $this->showOne($subtask);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateSubtaskRequest $request, int $id)
    {
        try {
            $subtask = $this->service->findOrFail($id);
            $subtask = $this->service->update($subtask, $request->all());
            return $this->showAfterAction($subtask, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $subtask = $this->service->findOrFail($id);
            $this->service->delete($subtask);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}