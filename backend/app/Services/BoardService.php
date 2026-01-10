<?php

namespace App\Services;

use App\Models\Board;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BoardService
{
    public function list(array $filters = []): Collection
    {
        $query = Board::query();
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function($q) use ($s){
                $q->where('title', 'ILIKE', "%$s%")
                  ->orWhere('description', 'ILIKE', "%$s%");
            });
        }
        return $query->latest()->get();
    }

    public function listWithLists(array $filters = []): Collection
    {
        $query = Board::with('lists.tasks');
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['search'])) {
            $s = $filters['search'];
            $query->where(function($q) use ($s){
                $q->where('title', 'ILIKE', "%$s%")
                  ->orWhere('description', 'ILIKE', "%$s%");
            });
        }
        return $query->latest()->get();
    }

    public function create(array $data): Board
    {
        $userId = Auth::id();
        $data['user_id'] = $userId;
        return Board::create($data);
    }

    public function findOrFail(int $id): Board
    {

        $board = Board::findOrFail($id);
        return $board;
    }

    public function update(Board $board, array $data): Board
    {
        // dd($board, $data);
        $board->update($data);
        return $board;
    }

    public function delete(Board $board): void
    {
        $board->delete();
    }
}
