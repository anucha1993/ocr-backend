<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiProviderService;

class DashboardController extends Controller
{
    public function __construct(
        private ApiProviderService $providerService,
    ) {}

    /**
     * Main admin dashboard — single page with 3 tabs.
     */
    public function index()
    {
        $providers = $this->providerService->getAllProviders();
        $activeProviders = $this->providerService->getActiveProviders();
        $endpoints = $this->providerService->getEndpoints();

        return view('admin.dashboard', compact('providers', 'activeProviders', 'endpoints'));
    }
}
