<?php

namespace Tests\Feature;

use App\Services\GoogleIdTokenVerifier;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use OpenSSLAsymmetricKey;
use Tests\TestCase;

/**
 * Exercises the REAL verifier — no mocks. Tokens are signed with a local RSA
 * key and Google's JWKS endpoint is faked to publish the matching public key,
 * so the full signature/exp/aud/iss path runs exactly as in production.
 */
class GoogleIdTokenVerifierTest extends TestCase
{
    private const CLIENT_ID = 'test-client.apps.googleusercontent.com';

    private const KID = 'test-key-1';

    private OpenSSLAsymmetricKey $privateKey;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => self::CLIENT_ID]);
        Cache::flush();

        $this->privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [$this->jwkFor($this->privateKey)],
            ]),
        ]);
    }

    public function test_accepts_a_validly_signed_token(): void
    {
        $claims = app(GoogleIdTokenVerifier::class)->verify($this->token());

        $this->assertNotNull($claims);
        $this->assertSame('108000000000000000001', $claims['sub']);
        $this->assertSame('user@example.com', $claims['email']);
        $this->assertTrue($claims['email_verified']);
    }

    public function test_rejects_wrong_audience(): void
    {
        $token = $this->token(['aud' => 'someone-else.apps.googleusercontent.com']);

        $this->assertNull(app(GoogleIdTokenVerifier::class)->verify($token));
    }

    public function test_rejects_unexpected_issuer(): void
    {
        $token = $this->token(['iss' => 'https://evil.example.com']);

        $this->assertNull(app(GoogleIdTokenVerifier::class)->verify($token));
    }

    public function test_rejects_expired_token(): void
    {
        $token = $this->token(['iat' => time() - 7200, 'exp' => time() - 3600]);

        $this->assertNull(app(GoogleIdTokenVerifier::class)->verify($token));
    }

    public function test_rejects_token_signed_with_a_foreign_key(): void
    {
        $foreignKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $this->assertNull(app(GoogleIdTokenVerifier::class)->verify($this->token([], $foreignKey)));
    }

    public function test_rejects_garbage_input(): void
    {
        $this->assertNull(app(GoogleIdTokenVerifier::class)->verify('not-a-jwt'));
    }

    /** Builds a Google-shaped JWKS entry for the given key. */
    private function jwkFor(OpenSSLAsymmetricKey $key): array
    {
        $rsa = openssl_pkey_get_details($key)['rsa'];

        return [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => self::KID,
            'n' => rtrim(strtr(base64_encode($rsa['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($rsa['e']), '+/', '-_'), '='),
        ];
    }

    /** Signs a Google-shaped ID token, optionally overriding claims or the key. */
    private function token(array $overrides = [], ?OpenSSLAsymmetricKey $key = null): string
    {
        $claims = array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => self::CLIENT_ID,
            'sub' => '108000000000000000001',
            'email' => 'user@example.com',
            'email_verified' => true,
            'name' => 'Test User',
            'iat' => time(),
            'exp' => time() + 3600,
        ], $overrides);

        return JWT::encode($claims, $key ?? $this->privateKey, 'RS256', self::KID);
    }
}
