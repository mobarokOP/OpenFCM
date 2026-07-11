<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server REST auth: validates a Bearer API key, attaches the owning
 * Application to the request. Used for server-to-server sends.
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer || ! str_starts_with($bearer, 'op_')) {
            return $this->unauthorized();
        }

        $key = ApiKey::where('hash', hash('sha256', $bearer))
            ->whereNull('revoked_at')
            ->first();

        if (! $key) {
            return $this->unauthorized();
        }

        $key->forceFill(['last_used_at' => now()])->saveQuietly();

        $app = $key->application;

        if (! $app || $app->status !== 'active') {
            return response()->json([
                'error' => ['code' => 'invalid_app', 'message' => 'Application inactive.'],
            ], 403);
        }

        $request->attributes->set('application', $app);
        $request->attributes->set('api_key', $key);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'error' => ['code' => 'invalid_api_key', 'message' => 'Invalid or missing API key.'],
        ], 401);
    }
}
