<?php

namespace App\Services;

use App\Models\ListModel;
use Illuminate\Database\Eloquent\Collection;

class ListService
{
    public function list(array $filters = []): Collection
    {
        $query = ListModel::query();
        if (isset($filters['board_id'])) {
            $query->where('board_id', $filters['board_id']);
        }
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%$search%");
            });
        }
        return $query->orderBy('order')->get();
    }

    public function listWithTasks(array $filters = [])
    {
        $query = ListModel::with([
            'tasks.components',
            'tasks.bugs',
        ]);
        if (isset($filters['board_id'])) {
            $query->where('board_id', $filters['board_id']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('title', 'ILIKE', "%$s%")
                    ->orWhere('description', 'ILIKE', "%$s%");
            });
        }
        return $query->orderBy('order')->get();
    }

    public function create(array $data): ListModel
    {
        return ListModel::create($data);
    }

    public function findOrFail(int $id): ListModel
    {
        return ListModel::findOrFail($id);
    }

    public function update(ListModel $list, array $data): ListModel
    {
        $list->update($data);
        return $list;
    }

    public function delete(ListModel $list): void
    {
        $list->delete();
    }
}
