<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Bugs;
use App\Models\Task;
use App\Models\TestCases;
use App\Services\RecordingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
                'duration_ms' => ['nullable', 'integer', 'min:0'],
                'file_size_bytes' => ['nullable', 'integer', 'min:0'],
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

    public function file(int $id)
    {
        $recording = $this->service->findOrFail($id);
        abort_unless($recording->file_path && Storage::disk('local')->exists($recording->file_path), 404);

        return response()->file(
            Storage::disk('local')->path($recording->file_path),
            [
                'Content-Type' => $recording->mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . basename($recording->file_path) . '"',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    public function logs(int $id)
    {
        $recording = $this->service->findOrFail($id);
        $disk = Storage::disk('local');

        return response()->json([
            'console' => $recording->console_log_path && $disk->exists($recording->console_log_path)
                ? json_decode($disk->get($recording->console_log_path), true)
                : [],
            'network' => $recording->network_log_path && $disk->exists($recording->network_log_path)
                ? json_decode($disk->get($recording->network_log_path), true)
                : [],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'title' => ['nullable', 'string', 'max:150'],
                'status' => ['nullable', 'string', 'in:recording,processing,completed,failed'],
                'duration_ms' => ['nullable', 'integer', 'min:0'],
                'file_size_bytes' => ['nullable', 'integer', 'min:0'],
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

    public function detach(int $id)
    {
        try {
            $recording = $this->service->findOrFail($id);
            $recording->recordable()->dissociate();
            $recording->save();
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
