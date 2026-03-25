<?php

namespace App\Services;

use App\Models\ApiEndpoint;
use App\Models\ApiProvider;
use Illuminate\Support\Str;

class ApiProviderService
{
    public function __construct(
        private ZohoTokenService $tokenService,
    ) {}

    /**
     * Get all providers with endpoint counts.
     */
    public function getAllProviders()
    {
        return ApiProvider::withCount('endpoints')->orderBy('name')->get();
    }

    /**
     * Get active providers only.
     */
    public function getActiveProviders()
    {
        return ApiProvider::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Find a provider by slug.
     */
    public function findBySlug(string $slug): ?ApiProvider
    {
        return ApiProvider::where('slug', $slug)->first();
    }

    /**
     * Create a new provider.
     */
    public function createProvider(array $data): ApiProvider
    {
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        return ApiProvider::create($data);
    }

    /**
     * Update an existing provider, keeping secrets if not provided.
     */
    public function updateProvider(ApiProvider $provider, array $data): ApiProvider
    {
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        // Keep existing secrets if not provided or masked
        $masked = '••••••••••••••••••••';
        if (empty($data['client_secret']) || $data['client_secret'] === $masked) {
            unset($data['client_secret']);
        }
        if (empty($data['refresh_token']) || $data['refresh_token'] === $masked) {
            unset($data['refresh_token']);
        }

        $provider->update($data);

        return $provider->fresh();
    }

    /**
     * Delete a provider and its endpoints.
     */
    public function deleteProvider(ApiProvider $provider): void
    {
        $provider->delete();
    }

    /**
     * Test connection by refreshing the OAuth token.
     */
    public function testConnection(ApiProvider $provider): array
    {
        $token = $this->tokenService->refreshToken($provider);

        if ($token) {
            return [
                'success' => true,
                'message' => "Connection successful! Token obtained for {$provider->name}.",
            ];
        }

        return [
            'success' => false,
            'message' => "Failed to connect to {$provider->name}. Check your credentials.",
        ];
    }

    /**
     * Get endpoints for a provider, optionally filtered.
     */
    public function getEndpoints(?int $providerId = null)
    {
        return ApiEndpoint::with('provider')
            ->when($providerId, fn($q) => $q->where('provider_id', $providerId))
            ->orderBy('name')
            ->get();
    }

    /**
     * Get active endpoints for a provider.
     */
    public function getActiveEndpoints(ApiProvider $provider)
    {
        return $provider->endpoints()
            ->where('is_active', true)
            ->get(['id', 'name', 'method', 'endpoint']);
    }
}
