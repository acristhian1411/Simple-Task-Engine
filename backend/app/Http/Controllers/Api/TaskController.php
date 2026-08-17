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

    public function indexByList(int $listId)
    {
        try {
            $data = $this->service->list([
                'list_id' => $listId,
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

    public function attachComponent(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'component_id' => ['required', 'integer', 'exists:components,id'],
            ]);
            $task = $this->service->findOrFail($id);
            $this->service->attachComponent($task, $data['component_id']);
            return $this->showMessage('Componente vinculado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function detachComponent(int $id, int $componentId)
    {
        try {
            $task = $this->service->findOrFail($id);
            $this->service->detachComponent($task, $componentId);
            return $this->showMessage('Componente desvinculado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function syncComponents(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'component_ids' => ['required', 'array'],
                'component_ids.*' => ['integer', 'exists:components,id'],
            ]);
            $task = $this->service->findOrFail($id);
            $this->service->syncComponents($task, $data['component_ids']);
            return $this->showMessage('Componentes sincronizados con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function attachBug(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'bug_id' => ['required', 'integer', 'exists:bugs,id'],
                'relation_type' => ['nullable', 'string', 'in:fixes,blocked_by,related'],
            ]);
            $task = $this->service->findOrFail($id);
            $this->service->attachBug($task, $data['bug_id'], $data['relation_type'] ?? 'related');
            return $this->showMessage('Bug vinculado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function detachBug(int $id, int $bugId)
    {
        try {
            $task = $this->service->findOrFail($id);
            $this->service->detachBug($task, $bugId);
            return $this->showMessage('Bug desvinculado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function syncBugs(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'bug_ids' => ['required', 'array'],
                'bug_ids.*' => ['integer', 'exists:bugs,id'],
            ]);
            $task = $this->service->findOrFail($id);
            $this->service->syncBugs($task, $data['bug_ids']);
            return $this->showMessage('Bugs sincronizados con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function blockedBy(int $id)
    {
        try {
            $task = $this->service->findOrFail($id);
            return $this->showAll($this->service->blockedBy($task));
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}
