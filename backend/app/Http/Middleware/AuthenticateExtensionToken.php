<?php

namespace App\Http\Middleware;

use App\Models\ExtentionTokens;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateExtensionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawToken = $request->bearerToken();
        $token = $rawToken
            ? ExtentionTokens::with('user')
                ->where('token_hash', hash('sha256', $rawToken))
                ->whereNull('revoked_at')
                ->first()
            : null;

        if (!$token?->user) {
            return response()->json(['error' => 'Token de extensión inválido o revocado'], 401);
        }

        $request->setUserResolver(fn () => $token->user);
        if (!$token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        return $next($request);
    }
}
