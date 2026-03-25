<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use App\Models\ApiProvider;
use App\Services\ApiProviderService;
use Illuminate\Http\Request;

class ApiEndpointController extends Controller
{
    public function __construct(
        private ApiProviderService $providerService,
    ) {}

    public function index(Request $request)
    {
        $providers = $this->providerService->getAllProviders();
        $selectedProviderId = $request->query('provider_id');

        $endpoints = $this->providerService->getEndpoints(
            $selectedProviderId ? (int) $selectedProviderId : null
        );

        return view('admin.endpoints.index', compact('endpoints', 'providers', 'selectedProviderId'));
    }

    public function create()
    {
        $providers = $this->providerService->getActiveProviders();
        return view('admin.endpoints.form', compact('providers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:api_providers,id',
            'name' => 'required|string|max:255',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'endpoint' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'default_headers' => 'nullable|json',
            'default_body' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['default_headers'] = $validated['default_headers'] ? json_decode($validated['default_headers'], true) : null;
        $validated['default_body'] = $validated['default_body'] ? json_decode($validated['default_body'], true) : null;

        ApiEndpoint::create($validated);

        return redirect()->route('admin.endpoints.index')
            ->with('success', 'API Endpoint created successfully.');
    }

    public function edit(ApiEndpoint $endpoint)
    {
        $providers = $this->providerService->getActiveProviders();
        return view('admin.endpoints.form', compact('endpoint', 'providers'));
    }

    public function update(Request $request, ApiEndpoint $endpoint)
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:api_providers,id',
            'name' => 'required|string|max:255',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'endpoint' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'default_headers' => 'nullable|json',
            'default_body' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['default_headers'] = $validated['default_headers'] ? json_decode($validated['default_headers'], true) : null;
        $validated['default_body'] = $validated['default_body'] ? json_decode($validated['default_body'], true) : null;

        $endpoint->update($validated);

        return redirect()->route('admin.endpoints.index')
            ->with('success', 'API Endpoint updated successfully.');
    }

    public function destroy(ApiEndpoint $endpoint)
    {
        $endpoint->delete();

        return redirect()->route('admin.endpoints.index')
            ->with('success', 'API Endpoint deleted successfully.');
    }
}
