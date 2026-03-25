<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiProvider;
use App\Services\ApiProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiProviderApiController extends Controller
{
    public function __construct(
        private ApiProviderService $providerService,
    ) {}

    public function index(): JsonResponse
    {
        $providers = $this->providerService->getAllProviders();

        $providers->each(function ($provider) {
            $provider->makeHidden(['client_secret', 'refresh_token', 'access_token']);
        });

        return response()->json($providers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'required|url|max:500',
            'token_url' => 'nullable|url|max:500',
            'client_id' => 'nullable|string|max:500',
            'client_secret' => 'nullable|string|max:500',
            'refresh_token' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $provider = $this->providerService->createProvider($validated);

        return response()->json($provider, 201);
    }

    public function show(ApiProvider $provider): JsonResponse
    {
        $provider->loadCount('endpoints');

        // Mask secrets
        $data = $provider->toArray();
        $data['has_client_secret'] = !empty($provider->client_secret);
        $data['has_refresh_token'] = !empty($provider->refresh_token);
        $data['client_secret'] = !empty($provider->client_secret) ? '••••••••••••••••••••' : '';
        $data['refresh_token'] = !empty($provider->refresh_token) ? '••••••••••••••••••••' : '';
        unset($data['access_token']);

        return response()->json($data);
    }

    public function update(Request $request, ApiProvider $provider): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'required|url|max:500',
            'token_url' => 'nullable|url|max:500',
            'client_id' => 'nullable|string|max:500',
            'client_secret' => 'nullable|string|max:500',
            'refresh_token' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->providerService->updateProvider($provider, $validated);

        return response()->json($provider->fresh());
    }

    public function destroy(ApiProvider $provider): JsonResponse
    {
        $this->providerService->deleteProvider($provider);
        return response()->json(null, 204);
    }

    public function testConnection(ApiProvider $provider): JsonResponse
    {
        $result = $this->providerService->testConnection($provider);
        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
