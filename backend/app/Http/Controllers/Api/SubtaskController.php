<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Subtask\StoreSubtaskRequest;
use App\Http\Requests\Subtask\UpdateSubtaskRequest;
use App\Services\BoardAccessService;
use App\Services\SubtaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubtaskController extends ApiController
{
    public function __construct(
        private SubtaskService $service,
        private BoardAccessService $access,
    ) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'task_id' => $request->query('task_id'),
                'user_id' => Auth::id() ?? null,
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
            $this->access->assertTask($taskId, Auth::id());
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
            $data = $request->validated();
            $this->access->assertTask((int) $data['task_id'], Auth::id());
            $subtask = $this->service->create($data);
            return $this->showAfterAction($subtask, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $this->access->assertSubtask($id, Auth::id());
            $subtask = $this->service->findOrFail($id);
            return $this->showOne($subtask);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateSubtaskRequest $request, int $id)
    {
        try {
            $this->access->assertSubtask($id, Auth::id());
            $data = $request->all();
            if (!empty($data['task_id'])) {
                $this->access->assertTask((int) $data['task_id'], Auth::id());
            }
            $subtask = $this->service->findOrFail($id);
            $subtask = $this->service->update($subtask, $data);
            return $this->showAfterAction($subtask, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->access->assertSubtask($id, Auth::id());
            $subtask = $this->service->findOrFail($id);
            $this->service->delete($subtask);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}