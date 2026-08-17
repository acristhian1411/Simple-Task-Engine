<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Bugs;
use App\Models\Task;
use App\Models\TestCases;
use App\Services\RecordingService;
use Illuminate\Http\Request;

class RecordingController extends ApiController
{
    public function __construct(private RecordingService $service) {}

    public function index(Request $request)
    {
        try {
            $data = $this->service->list([
                'status' => $request->query('status'),
                'recordable_type' => $request->query('recordable_type'),
                'recordable_id' => $request->query('recordable_id'),
            ]);
            return $this->showAll($data);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => ['nullable', 'string', 'max:150'],
                'status' => ['nullable', 'string', 'in:recording,processing,completed,failed'],
                'file_path' => ['nullable', 'string'],
                'mime_type' => ['nullable', 'string', 'max:50'],
                'duration_ms' => ['nullable', 'integer', 'min:0'],
                'file_size_bytes' => ['nullable', 'integer', 'min:0'],
                'console_log_path' => ['nullable', 'string'],
                'network_log_path' => ['nullable', 'string'],
                'recordable_type' => ['nullable', 'string'],
                'recordable_id' => ['nullable', 'integer'],
            ]);
            $recording = $this->service->create($data);
            return $this->showAfterAction($recording, 'create', 201);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $recording = $this->service->findOrFail($id);
            return $this->showOne($recording);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'title' => ['nullable', 'string', 'max:150'],
                'status' => ['nullable', 'string', 'in:recording,processing,completed,failed'],
                'file_path' => ['nullable', 'string'],
                'mime_type' => ['nullable', 'string', 'max:50'],
                'duration_ms' => ['nullable', 'integer', 'min:0'],
                'file_size_bytes' => ['nullable', 'integer', 'min:0'],
                'console_log_path' => ['nullable', 'string'],
                'network_log_path' => ['nullable', 'string'],
                'recordable_type' => ['nullable', 'string'],
                'recordable_id' => ['nullable', 'integer'],
            ]);
            $recording = $this->service->findOrFail($id);
            $recording = $this->service->update($recording, $data);
            return $this->showAfterAction($recording, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $recording = $this->service->findOrFail($id);
            $this->service->delete($recording);
            return $this->showMessage('Registro eliminado con exito');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function attachTo(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'recordable_type' => ['required', 'string'],
                'recordable_id' => ['required', 'integer'],
            ]);
            $recording = $this->service->findOrFail($id);
            $model = $this->resolveRecordable($data['recordable_type'], $data['recordable_id']);
            $recording = $this->service->attachTo($recording, $model);
            return $this->showAfterAction($recording, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function markCompleted(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'finished_at' => ['nullable', 'date'],
                'duration_ms' => ['nullable', 'integer', 'min:0'],
                'file_size_bytes' => ['nullable', 'integer', 'min:0'],
            ]);
            $recording = $this->service->findOrFail($id);
            $recording = $this->service->markCompleted($recording, $data);
            return $this->showAfterAction($recording, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function markFailed(int $id)
    {
        try {
            $recording = $this->service->findOrFail($id);
            $recording = $this->service->markFailed($recording);
            return $this->showAfterAction($recording, 'update');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    private function resolveRecordable(string $type, int $id): \Illuminate\Database\Eloquent\Model
    {
        return match (strtolower($type)) {
            'task', 'tasks', 'app\models\task' => Task::findOrFail($id),
            'bug', 'bugs', 'app\models\bugs' => Bugs::findOrFail($id),
            'test-case', 'test-cases', 'test_case', 'app\models\testcases' => TestCases::findOrFail($id),
            default => throw new \InvalidArgumentException("Tipo no soportado: {$type}"),
        };
    }
}