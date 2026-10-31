<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\TaskDependency\StoreTaskDependencyRequest;
use App\Http\Requests\TaskDependency\UpdateTaskDependencyRequest;
use App\Services\BoardAccessService;
use App\Services\TaskDependencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskDependencyController extends ApiController
{
    public function __construct(
        private TaskDependencyService $service,
        private BoardAccessService $access,
    ) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'task_id' => $request->query('task_id'),
                'depends_on_task_id' => $request->query('depends_on_task_id'),
                'user_id' => Auth::id() ?? null,
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

    public function store(StoreTaskDependencyRequest $request)
    {
        try {
            $data = $request->validated();
            $this->access->assertTask((int) $data['task_id'], Auth::id());
            $this->access->assertTask((int) $data['depends_on_task_id'], Auth::id());
            $dep = $this->service->create($data);
            return $this->showAfterAction($dep, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $this->access->assertDependency($id, Auth::id());
            $dep = $this->service->findOrFail($id);
            return $this->showOne($dep);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateTaskDependencyRequest $request, int $id)
    {
        try {
            $this->access->assertDependency($id, Auth::id());
            $data = $request->all();
            if (!empty($data['task_id'])) {
                $this->access->assertTask((int) $data['task_id'], Auth::id());
            }
            if (!empty($data['depends_on_task_id'])) {
                $this->access->assertTask((int) $data['depends_on_task_id'], Auth::id());
            }
            $dep = $this->service->findOrFail($id);
            $dep = $this->service->update($dep, $data);
            return $this->showAfterAction($dep, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->access->assertDependency($id, Auth::id());
            $dep = $this->service->findOrFail($id);
            $this->service->delete($dep);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}