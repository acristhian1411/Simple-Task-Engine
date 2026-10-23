<?php

namespace App\Services;

use App\Models\Board;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BoardService
{
    /**
     * Listas con las que arranca todo tablero nuevo. Los títulos coinciden con los
     * estilos de columna del front y "Hecho" es la que cuenta como lista terminada.
     */
    private const DEFAULT_LISTS = ['Por Hacer', 'En Progreso', 'Hecho'];

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

        return DB::transaction(function () use ($data) {
            $board = Board::create($data);
            foreach (self::DEFAULT_LISTS as $order => $title) {
                $board->lists()->create(['title' => $title, 'order' => $order]);
            }
            return $board;
        });
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
