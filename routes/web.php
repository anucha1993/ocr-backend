<?php

use App\Http\Controllers\Admin\ApiEndpointController;
use App\Http\Controllers\Admin\ApiProviderController;
use App\Http\Controllers\Admin\ApiTestController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->group(function () {
    // Dashboard — main 3-tab page
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // API Providers
    Route::resource('providers', ApiProviderController::class)->except('show');
    Route::post('providers/{provider}/test', [ApiProviderController::class, 'testConnection'])->name('providers.test');

    // API Endpoints
    Route::resource('endpoints', ApiEndpointController::class)->except('show');

    // Test API
    Route::get('api-test', [ApiTestController::class, 'index'])->name('test.index');
    Route::post('api-test/execute', [ApiTestController::class, 'execute'])->name('test.execute');
    Route::get('api-test/endpoints/{provider}', [ApiTestController::class, 'getEndpoints'])->name('test.endpoints');
});
