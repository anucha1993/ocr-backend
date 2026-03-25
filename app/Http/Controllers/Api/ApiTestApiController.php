<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiProvider;
use App\Services\ApiProviderService;
use App\Services\ExternalApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTestApiController extends Controller
{
    public function __construct(
        private ExternalApiService $apiService,
        private ApiProviderService $providerService,
    ) {}

    public function execute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:api_providers,id',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'url' => 'required|string|max:1000',
            'headers' => 'nullable',
            'body' => 'nullable',
        ]);

        $provider = ApiProvider::findOrFail($validated['provider_id']);

        $headers = [];
        if (!empty($validated['headers'])) {
            $decoded = is_string($validated['headers'])
                ? json_decode($validated['headers'], true)
                : $validated['headers'];
            if (is_array($decoded)) {
                $headers = $decoded;
            }
        }

        $body = null;
        if (!empty($validated['body'])) {
            $decoded = is_string($validated['body'])
                ? json_decode($validated['body'], true)
                : $validated['body'];
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        $fullUrl = rtrim($provider->base_url, '/') . '/' . ltrim($validated['url'], '/');

        $startTime = microtime(true);
        $result = $this->apiService->executeRaw($provider, $validated['method'], $fullUrl, $headers, $body);
        $duration = round((microtime(true) - $startTime) * 1000);

        $result['duration_ms'] = $duration;

        return response()->json($result);
    }

    public function getEndpoints(ApiProvider $provider): JsonResponse
    {
        $endpoints = $this->providerService->getActiveEndpoints($provider);
        return response()->json($endpoints);
    }
}
