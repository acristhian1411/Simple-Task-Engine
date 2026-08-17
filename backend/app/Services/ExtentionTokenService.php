<?php

namespace App\Services;

use App\Models\ExtentionTokens;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ExtentionTokenService
{
    public function list(array $filters = []): Collection
    {
        $query = ExtentionTokens::query();
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['revoked'])) {
            if ($filters['revoked'] === 'true' || $filters['revoked'] === true) {
                $query->whereNotNull('revoked_at');
            }
        }
        return $query->latest()->get();
    }

    public function listWithRelations(array $filters = []): Collection
    {
        $query = ExtentionTokens::with('user');
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['revoked']) && ($filters['revoked'] === 'true' || $filters['revoked'] === true)) {
            $query->whereNotNull('revoked_at');
        }
        return $query->latest()->get();
    }

    public function create(array $data): ExtentionTokens
    {
        $data['user_id'] = $data['user_id'] ?? Auth::id();
        return ExtentionTokens::create($data);
    }

    public function findOrFail(int $id): ExtentionTokens
    {
        return ExtentionTokens::findOrFail($id);
    }

    public function update(ExtentionTokens $token, array $data): ExtentionTokens
    {
        $token->update($data);
        return $token;
    }

    public function delete(ExtentionTokens $token): void
    {
        $token->delete();
    }

    public function issue(User $user, ?string $label = null): ExtentionTokens
    {
        $raw = bin2hex(random_bytes(32));
        $token = ExtentionTokens::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $raw),
            'label' => $label,
        ]);
        $token->setAttribute('token', $raw);
        return $token;
    }

    public function revoke(ExtentionTokens $token): ExtentionTokens
    {
        $token->update(['revoked_at' => now()]);
        return $token;
    }

    public function touchLastUsed(ExtentionTokens $token): ExtentionTokens
    {
        $token->update(['last_used_at' => now()]);
        return $token;
    }

    public function resolveFromRawToken(string $raw): ?ExtentionTokens
    {
        return ExtentionTokens::where('token_hash', hash('sha256', $raw))
            ->whereNull('revoked_at')
            ->first();
    }
}