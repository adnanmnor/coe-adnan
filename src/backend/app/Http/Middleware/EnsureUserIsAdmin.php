<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cuba guard default (sanctum) dahulu
        $user = $request->user();

        // Fallback ke guard sanctum secara explicit
        if (! $user) {
            $user = $request->user('sanctum');
        }

        if (! $user || $user->role !== 'admin') {
            return response()->json([
                'message' => 'Forbidden. Admin role required.',
                'debug' => [
                    'has_user' => $user !== null,
                    'role' => $user?->role,
                ],
            ], 403);
        }

        return $next($request);
    }
}