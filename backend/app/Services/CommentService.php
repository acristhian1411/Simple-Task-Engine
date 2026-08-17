<?php

namespace App\Services;

use App\Models\Comments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CommentService
{
    public function list(array $filters = []): Collection
    {
        $query = Comments::query();
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('title', 'ILIKE', "%$s%")
                    ->orWhere('content', 'ILIKE', "%$s%");
            });
        }
        return $query->latest()->get();
    }

    public function listWithRelations(array $filters = []): Collection
    {
        $query = Comments::with('user', 'commentable');
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('title', 'ILIKE', "%$s%")
                    ->orWhere('content', 'ILIKE', "%$s%");
            });
        }
        return $query->latest()->get();
    }

    public function create(array $data): Comments
    {
        $userId = Auth::id();
        $data['user_id'] = $userId;
        $data['commentable_type'] = $data['commentable_type'] ?? null;
        $data['commentable_id'] = $data['commentable_id'] ?? null;
        return Comments::create($data);
    }

    public function findOrFail(int $id): Comments
    {

        $Comments = Comments::findOrFail($id);
        return $Comments;
    }

    public function update(Comments $Comments, array $data): Comments
    {
        $Comments->update($data);
        return $Comments;
    }

    public function delete(Comments $Comments): void
    {
        $Comments->delete();
    }

    public function forModel(Model $model): Collection
    {
        return Comments::with('user')
            ->where('commentable_type', $model->getMorphClass())
            ->where('commentable_id', $model->getKey())
            ->latest()
            ->get();
    }
}
