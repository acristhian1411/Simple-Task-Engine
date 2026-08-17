<?php

namespace App\Services;

use App\Models\Recordings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class RecordingService
{
    public function list(array $filters = []): Collection
    {
        $query = Recordings::query();
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['recordable_type'])) {
            $query->where('recordable_type', $filters['recordable_type']);
        }
        if (isset($filters['recordable_id'])) {
            $query->where('recordable_id', $filters['recordable_id']);
        }
        return $query->latest()->get();
    }

    public function create(array $data): Recordings
    {
        $data['recorded_by_id'] = $data['recorded_by_id'] ?? Auth::id();
        return Recordings::create($data);
    }

    public function findOrFail(int $id): Recordings
    {
        return Recordings::findOrFail($id);
    }

    public function update(Recordings $recording, array $data): Recordings
    {
        $recording->update($data);
        return $recording;
    }

    public function delete(Recordings $recording): void
    {
        $recording->delete();
    }

    public function attachTo(Recordings $recording, Model $model): Recordings
    {
        $recording->recordable()->associate($model);
        $recording->save();
        return $recording;
    }

    public function markCompleted(Recordings $recording, array $data): Recordings
    {
        $recording->update([
            'status' => 'completed',
            'finished_at' => $data['finished_at'] ?? now(),
            'duration_ms' => $data['duration_ms'] ?? $recording->duration_ms,
            'file_size_bytes' => $data['file_size_bytes'] ?? $recording->file_size_bytes,
        ]);
        return $recording;
    }

    public function markFailed(Recordings $recording): Recordings
    {
        $recording->update(['status' => 'failed']);
        return $recording;
    }

    public function forModel(Model $model): Collection
    {
        return Recordings::where('recordable_type', $model->getMorphClass())
            ->where('recordable_id', $model->getKey())
            ->latest()
            ->get();
    }
}