<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Bugs;
use App\Models\Recordings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExtensionController extends ApiController
{
    public function session(Request $request)
    {
        return response()->json(['data' => $request->user()->only('id', 'name', 'email')]);
    }

    public function bugs()
    {
        return response()->json([
            'data' => Bugs::query()->latest()->get(['id', 'title', 'status']),
        ]);
    }

    public function createBug(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:20000'],
            'severity' => ['required', 'string', 'in:low,medium,high,critical'],
        ]);

        $bug = Bugs::create($data + [
            'status' => 'open',
            'reported_by_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $bug->only('id', 'title', 'status')], 201);
    }

    public function storeRecording(Request $request)
    {
        $data = $request->validate([
            'video' => ['required', 'file', 'max:512000', 'mimes:webm,mp4,mov'],
            'title' => ['nullable', 'string', 'max:150'],
            'duration_ms' => ['nullable', 'integer', 'min:0'],
            'bug_id' => ['nullable', 'integer', 'exists:bugs,id'],
            'console_logs' => ['nullable', 'string', 'max:10485760'],
            'network_logs' => ['nullable', 'string', 'max:20971520'],
        ]);

        foreach (['console_logs', 'network_logs'] as $field) {
            if (isset($data[$field])) {
                $logs = json_decode($data[$field]);
                if (!is_array($logs) || !array_is_list($logs)) {
                    throw ValidationException::withMessages([$field => 'El contenido debe ser un array JSON válido.']);
                }
            }
        }

        $disk = Storage::disk('local');
        $directory = 'recordings/' . $request->user()->id;
        $uuid = (string) Str::uuid();
        $paths = [];

        try {
            $videoPath = $request->file('video')->storeAs(
                $directory,
                "{$uuid}.{$request->file('video')->extension()}",
                'local',
            );
            if (!$videoPath) {
                throw new \RuntimeException('No se pudo guardar el video.');
            }
            $paths['video'] = $videoPath;
            foreach (['console_logs' => 'console', 'network_logs' => 'network'] as $field => $name) {
                if (isset($data[$field])) {
                    $paths[$name] = "{$directory}/{$uuid}-{$name}.json";
                    if (!$disk->put($paths[$name], $data[$field])) {
                        throw new \RuntimeException('No se pudieron guardar los registros.');
                    }
                }
            }

            $recording = DB::transaction(fn () => Recordings::create([
                'title' => $data['title'] ?? null,
                'status' => 'completed',
                'file_path' => $paths['video'],
                'mime_type' => $request->file('video')->getMimeType() ?: 'video/webm',
                'duration_ms' => $data['duration_ms'] ?? null,
                'file_size_bytes' => $request->file('video')->getSize(),
                'console_log_path' => $paths['console'] ?? null,
                'network_log_path' => $paths['network'] ?? null,
                'recordable_type' => isset($data['bug_id']) ? (new Bugs())->getMorphClass() : null,
                'recordable_id' => $data['bug_id'] ?? null,
                'recorded_by_id' => $request->user()->id,
                'finished_at' => now(),
            ]));
        } catch (\Throwable $e) {
            $disk->delete(array_values($paths));
            throw $e;
        }

        return response()->json(['data' => $recording], 201);
    }
}
