<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Bug\StoreBugRequest;
use App\Http\Requests\Bug\UpdateBugRequest;
use App\Models\Recordings;
use App\Models\Task;
use App\Services\BugService;
use Illuminate\Http\Request;

class BugController extends ApiController
{
    public function __construct(private BugService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->listWithRelations([
                'test_case_id' => $request->query('test_case_id'),
                'test_step_id' => $request->query('test_step_id'),
                'status' => $request->query('status'),
                'severity' => $request->query('severity'),
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

    public function store(StoreBugRequest $request)
    {
        try {
            $bug = $this->service->create($request->validated());
            return $this->showAfterAction($bug, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $bug = $this->service->findOrFail($id);
            return $this->showOne($bug);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(UpdateBugRequest $request, int $id)
    {
        try {
            $bug = $this->service->findOrFail($id);
            $bug = $this->service->update($bug, $request->all());
            return $this->showAfterAction($bug, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $bug = $this->service->findOrFail($id);
            $this->service->delete($bug);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function listByStatus(string $status)
    {
        try {
            return $this->showAll($this->service->listByStatus($status));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function listBySeverity(string $severity)
    {
        try {
            return $this->showAll($this->service->listBySeverity($severity));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function changeStatus(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'status' => ['required', 'string', 'in:open,in_progress,resolved,closed'],
            ]);
            $bug = $this->service->findOrFail($id);
            $bug = $this->service->changeStatus($bug, $data['status']);
            return $this->showAfterAction($bug, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function linkTask(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'task_id' => ['required', 'integer', 'exists:tasks,id'],
                'relation_type' => ['nullable', 'string', 'in:fixes,blocked_by,related'],
            ]);
            $bug = $this->service->findOrFail($id);
            $task = Task::findOrFail($data['task_id']);
            $this->service->linkTask($bug, $task, $data['relation_type'] ?? 'related');
            return $this->showMessage('Tarea vinculada con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function unlinkTask(int $id, int $taskId)
    {
        try {
            $bug = $this->service->findOrFail($id);
            $task = Task::findOrFail($taskId);
            $this->service->unlinkTask($bug, $task);
            return $this->showMessage('Tarea desvinculada con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function tasks(int $id)
    {
        try {
            $bug = $this->service->findOrFail($id);
            return $this->showAll($this->service->tasks($bug));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function attachRecording(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'recording_id' => ['required', 'integer', 'exists:recordings,id'],
            ]);
            $bug = $this->service->findOrFail($id);
            $recording = Recordings::findOrFail($data['recording_id']);
            $recording = $this->service->attachRecording($bug, $recording);
            return $this->showAfterAction($recording, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}