<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifies a Google Identity Services ID token (the "credential" returned by
 * "Continue with Google") and returns its claims, or null when invalid.
 *
 * Verification happens locally with firebase/php-jwt against Google's
 * published JWKS. google/auth's AccessToken::verify() is deliberately not
 * used: its RS256 path requires phpseclib, which this project doesn't ship.
 */
class GoogleIdTokenVerifier
{
    private const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const JWKS_CACHE_KEY = 'openfcm:google-oauth-jwks';

    private const VALID_ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

    /** Seconds of clock skew tolerated on iat/exp. */
    private const LEEWAY = 60;

    public function verify(string $idToken): ?array
    {
        $clientId = config('services.google.client_id');

        if (empty($clientId)) {
            return null;
        }

        try {
            $claims = $this->decode($idToken);
        } catch (Throwable) {
            // Google rotates its signing keys; retry once with a fresh set.
            Cache::forget(self::JWKS_CACHE_KEY);

            try {
                $claims = $this->decode($idToken);
            } catch (Throwable $e) {
                Log::warning('Google ID token rejected: '.$e->getMessage());

                return null;
            }
        }

        if (($claims['aud'] ?? null) !== $clientId) {
            Log::warning('Google ID token rejected: audience mismatch.');

            return null;
        }

        if (! in_array($claims['iss'] ?? null, self::VALID_ISSUERS, true)) {
            Log::warning('Google ID token rejected: unexpected issuer.');

            return null;
        }

        return $claims;
    }

    /**
     * Checks the RS256 signature against Google's published keys plus the
     * exp/iat timestamps, and returns the claims. Throws when invalid.
     */
    private function decode(string $idToken): array
    {
        JWT::$leeway = self::LEEWAY;

        return (array) JWT::decode($idToken, JWK::parseKeySet($this->jwks(), 'RS256'));
    }

    /** Google's OAuth2 signing keys, cached well under their rotation window. */
    private function jwks(): array
    {
        return Cache::remember(self::JWKS_CACHE_KEY, now()->addHours(6), function () {
            return Http::timeout(10)->get(self::JWKS_URL)->throw()->json();
        });
    }
}
