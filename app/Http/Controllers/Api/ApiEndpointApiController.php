<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use App\Services\ApiProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiEndpointApiController extends Controller
{
    public function __construct(
        private ApiProviderService $providerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $providerId = $request->query('provider_id');
        $endpoints = $this->providerService->getEndpoints(
            $providerId ? (int) $providerId : null
        );

        return response()->json($endpoints);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:api_providers,id',
            'name' => 'required|string|max:255',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'endpoint' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'default_headers' => 'nullable',
            'default_body' => 'nullable',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        if (is_string($validated['default_headers'] ?? null)) {
            $validated['default_headers'] = json_decode($validated['default_headers'], true);
        }
        if (is_string($validated['default_body'] ?? null)) {
            $validated['default_body'] = json_decode($validated['default_body'], true);
        }

        $endpoint = ApiEndpoint::create($validated);

        return response()->json($endpoint->load('provider'), 201);
    }

    public function show(ApiEndpoint $endpoint): JsonResponse
    {
        return response()->json($endpoint->load('provider'));
    }

    public function update(Request $request, ApiEndpoint $endpoint): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:api_providers,id',
            'name' => 'required|string|max:255',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'endpoint' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'default_headers' => 'nullable',
            'default_body' => 'nullable',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        if (is_string($validated['default_headers'] ?? null)) {
            $validated['default_headers'] = json_decode($validated['default_headers'], true);
        }
        if (is_string($validated['default_body'] ?? null)) {
            $validated['default_body'] = json_decode($validated['default_body'], true);
        }

        $endpoint->update($validated);

        return response()->json($endpoint->fresh()->load('provider'));
    }

    public function destroy(ApiEndpoint $endpoint): JsonResponse
    {
        $endpoint->delete();
        return response()->json(null, 204);
    }
}
