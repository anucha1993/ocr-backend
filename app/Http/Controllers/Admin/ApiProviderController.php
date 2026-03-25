<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiProvider;
use App\Services\ApiProviderService;
use Illuminate\Http\Request;

class ApiProviderController extends Controller
{
    public function __construct(
        private ApiProviderService $providerService,
    ) {}

    public function index()
    {
        $providers = $this->providerService->getAllProviders();
        return view('admin.providers.index', compact('providers'));
    }

    public function create()
    {
        return view('admin.providers.form');
    }

    public function store(Request $request)
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

        $this->providerService->createProvider($validated);

        return redirect()->route('admin.providers.index')
            ->with('success', 'API Provider created successfully.');
    }

    public function edit(ApiProvider $provider)
    {
        return view('admin.providers.form', compact('provider'));
    }

    public function update(Request $request, ApiProvider $provider)
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

        return redirect()->route('admin.providers.index')
            ->with('success', 'API Provider updated successfully.');
    }

    public function destroy(ApiProvider $provider)
    {
        $this->providerService->deleteProvider($provider);

        return redirect()->route('admin.providers.index')
            ->with('success', 'API Provider deleted successfully.');
    }

    public function testConnection(ApiProvider $provider)
    {
        $result = $this->providerService->testConnection($provider);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }
}
