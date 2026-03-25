<?php

namespace App\Services;

use App\Models\ApiEndpoint;
use App\Models\ApiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalApiService
{
    public function __construct(
        private ZohoTokenService $tokenService,
    ) {}

    /**
     * Call a named endpoint for a given provider slug.
     */
    public function callEndpoint(
        string $providerSlug,
        string $endpointName,
        array $data = [],
        array $pathParams = [],
    ): array {
        $provider = ApiProvider::where('slug', $providerSlug)->where('is_active', true)->first();

        if (!$provider) {
            return ['success' => false, 'error' => "Provider '{$providerSlug}' not found or inactive."];
        }

        $endpoint = $provider->endpoints()
            ->where('name', $endpointName)
            ->where('is_active', true)
            ->first();

        if (!$endpoint) {
            return ['success' => false, 'error' => "Endpoint '{$endpointName}' not found or inactive."];
        }

        return $this->executeRequest($provider, $endpoint, $data, $pathParams);
    }

    /**
     * Execute a raw API request (used by the Test API tool).
     */
    public function executeRaw(
        ApiProvider $provider,
        string $method,
        string $url,
        array $headers = [],
        mixed $body = null,
    ): array {
        $accessToken = $this->tokenService->getAccessToken($provider);

        $request = Http::withHeaders(array_merge(
            ['Authorization' => 'Zoho-oauthtoken ' . $accessToken],
            $headers,
        ))->timeout(30);

        return $this->send($request, $method, $url, $body);
    }

    /**
     * Execute a request for a defined endpoint.
     */
    private function executeRequest(
        ApiProvider $provider,
        ApiEndpoint $endpoint,
        array $data,
        array $pathParams,
    ): array {
        $accessToken = $this->tokenService->getAccessToken($provider);

        if (!$accessToken) {
            return ['success' => false, 'error' => 'Unable to obtain access token.'];
        }

        // Build URL with path parameters
        $path = $endpoint->endpoint;
        foreach ($pathParams as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        $url = rtrim($provider->base_url, '/') . '/' . ltrim($path, '/');

        // Merge default headers
        $headers = array_merge(
            $endpoint->default_headers ?? [],
            ['Authorization' => 'Zoho-oauthtoken ' . $accessToken],
        );

        $request = Http::withHeaders($headers)->timeout(30);

        // Merge default body
        $body = array_merge($endpoint->default_body ?? [], $data);

        return $this->send($request, $endpoint->method, $url, $body ?: null);
    }

    private function send($request, string $method, string $url, mixed $body): array
    {
        try {
            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $body ?? []),
                'POST' => $request->post($url, $body),
                'PUT' => $request->put($url, $body),
                'PATCH' => $request->patch($url, $body),
                'DELETE' => $request->delete($url, $body ?? []),
                default => $request->get($url),
            };

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json() ?? $response->body(),
                'headers' => $response->headers(),
            ];
        } catch (\Exception $e) {
            Log::error('API request failed', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
