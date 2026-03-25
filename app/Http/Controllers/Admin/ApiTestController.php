<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiProvider;
use App\Services\ApiProviderService;
use App\Services\ExternalApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTestController extends Controller
{
    public function __construct(
        private ExternalApiService $apiService,
        private ApiProviderService $providerService,
    ) {}

    public function index()
    {
        $providers = $this->providerService->getActiveProviders();

        return view('admin.test.index', compact('providers'));
    }

    public function execute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:api_providers,id',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'url' => 'required|string|max:1000',
            'headers' => 'nullable|string',
            'body' => 'nullable|string',
        ]);

        $provider = ApiProvider::findOrFail($validated['provider_id']);

        $headers = [];
        if (!empty($validated['headers'])) {
            $decoded = json_decode($validated['headers'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $headers = $decoded;
            }
        }

        $body = null;
        if (!empty($validated['body'])) {
            $decoded = json_decode($validated['body'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
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
