<?php

namespace App\Services;

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class BoardService
{
    /**
     * Listas con las que arranca todo tablero nuevo. Los títulos coinciden con los
     * estilos de columna del front y "Hecho" es la que cuenta como lista terminada.
     */
    private const DEFAULT_LISTS = ['Por Hacer', 'En Progreso', 'Hecho'];

    public function __construct(private BoardAccessService $access) {}

    public function list(array $filters = []): Collection
    {
        $query = Board::query();
        if (isset($filters['user_id'])) {
            $this->access->scopeAccessible($query, (int) $filters['user_id']);
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
        $query = Board::with(['lists.tasks', 'owner', 'members']);
        if (isset($filters['user_id'])) {
            $this->access->scopeAccessible($query, (int) $filters['user_id']);
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
        return Board::findOrFail($id);
    }

    public function findForUserOrFail(int $id, int $userId): Board
    {
        return $this->access->accessibleBoards($userId)->whereKey($id)->firstOrFail();
    }

    public function findOwnedOrFail(int $id, int $userId): Board
    {
        return Board::whereKey($id)->where('user_id', $userId)->firstOrFail();
    }

    public function update(Board $board, array $data): Board
    {
        $board->update($data);
        return $board;
    }

    public function delete(Board $board): void
    {
        $board->delete();
    }

    /**
     * Miembros del tablero, con el dueño primero.
     */
    public function members(Board $board): array
    {
        $board->loadMissing(['owner', 'members']);

        $owner = [
            'id' => $board->owner->id,
            'name' => $board->owner->name,
            'email' => $board->owner->email,
            'role' => 'owner',
        ];

        $members = $board->members
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'member',
            ])
            ->all();

        return array_merge([$owner], $members);
    }

    public function addMember(Board $board, string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No existe un usuario con ese email.'],
            ]);
        }

        if ($user->id === $board->user_id) {
            throw ValidationException::withMessages([
                'email' => ['El dueño ya tiene acceso al tablero.'],
            ]);
        }

        $board->members()->syncWithoutDetaching([$user->id]);

        return $this->members($board);
    }

    public function removeMember(Board $board, int $userId): array
    {
        $board->members()->detach($userId);

        return $this->members($board);
    }
}
