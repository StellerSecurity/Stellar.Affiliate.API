<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RevolutBusinessClient
{
    private ?string $accessToken = null;
    private int $expiresAt = 0;

    public function environment(): string
    {
        $environment = (string) config('payouts.revolut.environment');
        if (! in_array($environment, ['sandbox', 'production'], true)) {
            throw new RuntimeException('Invalid Revolut environment.');
        }

        return $environment;
    }

    private function baseUrl(): string
    {
        return $this->environment() === 'production'
            ? 'https://b2b.revolut.com/api/1.0'
            : 'https://sandbox-b2b.revolut.com/api/1.0';
    }

    public function authenticate(): void
    {
        if ($this->accessToken && time() < $this->expiresAt) {
            return;
        }
        foreach (['client_id', 'issuer', 'refresh_token'] as $key) {
            if (! config('payouts.revolut.'.$key)) {
                throw new RuntimeException('Missing Revolut setting: '.$key);
            }
        }
        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $assertion = $encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)).'.'.$encode(json_encode([
            'iss' => config('payouts.revolut.issuer'),
            'sub' => config('payouts.revolut.client_id'),
            'aud' => 'https://revolut.com',
            'exp' => time() + 300,
        ], JSON_THROW_ON_ERROR));
        $configuredKey = (string) config('payouts.revolut.private_key_base64');
        if ($configuredKey !== '') {
            $keyMaterial = base64_decode($configuredKey, true);
            if (! is_string($keyMaterial) || $keyMaterial === '') {
                throw new RuntimeException('Invalid base64-encoded Revolut private key.');
            }
        } else {
            $path = (string) config('payouts.revolut.private_key_path');
            if ($path === '') {
                throw new RuntimeException('Missing Revolut private key setting.');
            }
            $keyMaterial = 'file://'.$path;
        }
        $key = @openssl_pkey_get_private($keyMaterial);
        if (! $key || ! openssl_sign($assertion, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Cannot sign Revolut client assertion.');
        }
        $response = Http::asForm()->acceptJson()->timeout(30)->connectTimeout(10)
            ->withOptions(['allow_redirects' => false])->post($this->baseUrl().'/auth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => config('payouts.revolut.refresh_token'),
                'client_id' => config('payouts.revolut.client_id'),
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => $assertion.'.'.$encode($signature),
            ]);
        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            // Do not put response bodies or credentials in exceptions/logs.
            throw new RuntimeException('Revolut authentication failed (HTTP '.$response->status().').');
        }
        $this->accessToken = $response->json('access_token');
        $this->expiresAt = time() + max(1, (int) $response->json('expires_in', 2400) - 60);
    }

    private function http(): PendingRequest
    {
        $this->authenticate();

        return Http::baseUrl($this->baseUrl())->withToken($this->accessToken)->acceptJson()
            ->timeout(30)->connectTimeout(10)->withOptions(['allow_redirects' => false]);
    }

    public function lookup(string $requestId): ?array
    {
        $response = $this->http()->get('/transaction/'.rawurlencode($requestId), ['id_type' => 'request_id']);
        if ($response->status() === 404) {
            return null;
        }
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('Revolut transaction lookup failed.');
        }

        return $response->json();
    }

    public function pay(array $payload): array
    {
        // Intentionally no HTTP retries. An uncertain submission is reconciled by request_id.
        $response = $this->http()->post('/pay', $payload);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('Revolut submission needs reconciliation.');
        }

        return $response->json();
    }

    public function get(string $path, array $query = []): array
    {
        $response = $this->http()->get($path, $query);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('Revolut account validation failed.');
        }

        return $response->json();
    }

    public function post(string $path, array $payload): array
    {
        $response = $this->http()->post($path, $payload);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('Revolut bank setup or payment preflight failed.');
        }

        return $response->json();
    }
}
