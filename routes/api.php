<?php

use App\Http\Controllers\Api\ApiEndpointApiController;
use App\Http\Controllers\Api\ApiProviderApiController;
use App\Http\Controllers\Api\ApiTestApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\DocumentTypeRuleController;
use App\Http\Controllers\Api\ForeignDataController;
use App\Http\Controllers\Api\IdCardController;
use App\Http\Controllers\Api\IdCardReaderSettingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OcrController;
use App\Http\Controllers\Api\PassportMappingController;
use App\Http\Controllers\Api\ScanBatchController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ZohoSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Auth (public) ─────────────────────────────────────────
Route::middleware('throttle:10,1')->post('/auth/login', [AuthController::class, 'login']);

// ─── Authenticated routes ──────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Foreign Data (Zoho CRM) — all authenticated users
    Route::get('/foreign-data', [ForeignDataController::class, 'index']);
    Route::get('/foreign-data/search', [ForeignDataController::class, 'searchByPassport']);
    Route::get('/foreign-data/{id}', [ForeignDataController::class, 'show']);
    Route::post('/foreign-data', [ForeignDataController::class, 'store']);
    Route::put('/foreign-data/{id}', [ForeignDataController::class, 'update']);
    Route::delete('/foreign-data/{id}', [ForeignDataController::class, 'destroy']);

    // ID Card / Labours
    Route::get('/idcard', [IdCardController::class, 'index']);
    Route::post('/idcard', [IdCardController::class, 'store']);
    Route::get('/idcard/{labour}', [IdCardController::class, 'show']);
    Route::delete('/idcard/{labour}', [IdCardController::class, 'destroy']);

    // Scan Batches
    Route::get('/scan-batches', [ScanBatchController::class, 'index']);
    Route::post('/scan-batches', [ScanBatchController::class, 'store']);
    Route::get('/scan-batches/{scanBatch}', [ScanBatchController::class, 'show']);
    Route::patch('/scan-batches/{scanBatch}/visibility', [ScanBatchController::class, 'updateVisibility']);
    Route::get('/scan-batches/{scanBatch}/export', [ScanBatchController::class, 'export']);
    Route::delete('/scan-batches/{scanBatch}', [ScanBatchController::class, 'destroy']);

    // ID Card Reader Settings (read = all, write = admin)
    Route::get('/idcard-reader-settings', [IdCardReaderSettingController::class, 'show']);

    // Document Type Rules (read = all for auto-calc, write = admin)
    Route::get('/document-type-rules', [DocumentTypeRuleController::class, 'index']);

    // Passport Mappings (read = all for id-card-reader, write = admin)
    Route::get('/passport-mappings', [PassportMappingController::class, 'index']);
    Route::get('/passport-mappings/{passportMapping}', [PassportMappingController::class, 'show']);

    // OCR field mappings (read = all for OCR processing)
    Route::get('/ocr/field-mappings', [OcrController::class, 'fieldMappings']);
    Route::get('/ocr/default-fields', [OcrController::class, 'defaultFields']);

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{notification}', [NotificationController::class, 'destroy']);
    });

    // ─── OCR Processing ────────────────────────────────────
    Route::prefix('ocr')->group(function () {
        // Process files (upload + OCR) — rate limited to control Cloud API costs
        Route::middleware('throttle:20,1')->post('/process', [OcrController::class, 'process']);

        // Preview OCR (Smart Scan template builder)
        Route::post('/preview', [OcrController::class, 'preview']);

        // Batch results
        Route::get('/batch/{batchId}', [OcrController::class, 'batch']);
        Route::get('/batch/{batchId}/export', [OcrController::class, 'exportBatch']);
        // Save OCR batch results as labour records
        Route::post('/batch/{batchId}/save-labours', [OcrController::class, 'saveBatchAsLabours']);

        // Results (list, show)
        Route::get('/results', [OcrController::class, 'results']);
        Route::get('/results/{ocrResult}', [OcrController::class, 'showResult']);
    });

    // ─── Admin-only routes ─────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        // OCR Results (delete)
        Route::delete('/ocr/results/{ocrResult}', [OcrController::class, 'destroyResult']);

        // ID Card Reader Settings (write)
        Route::put('/idcard-reader-settings', [IdCardReaderSettingController::class, 'update']);

        // Passport Mappings (write)
        Route::post('/passport-mappings', [PassportMappingController::class, 'store']);
        Route::put('/passport-mappings/{passportMapping}', [PassportMappingController::class, 'update']);
        Route::delete('/passport-mappings/{passportMapping}', [PassportMappingController::class, 'destroy']);

        // Document Type Rules (write)
        Route::post('/document-type-rules', [DocumentTypeRuleController::class, 'store']);
        Route::put('/document-type-rules/{documentTypeRule}', [DocumentTypeRuleController::class, 'update']);
        Route::delete('/document-type-rules/{documentTypeRule}', [DocumentTypeRuleController::class, 'destroy']);

        // OCR Field Mappings (write)
        Route::post('/ocr/field-mappings', [OcrController::class, 'storeFieldMapping']);
        Route::put('/ocr/field-mappings/{ocrFieldMapping}', [OcrController::class, 'updateFieldMapping']);
        Route::delete('/ocr/field-mappings/{ocrFieldMapping}', [OcrController::class, 'destroyFieldMapping']);

        // Zoho Settings
        Route::prefix('zoho')->group(function () {
            Route::get('/settings', [ZohoSettingController::class, 'show']);
            Route::post('/settings', [ZohoSettingController::class, 'saveSettings']);
            Route::post('/test-connection', [ZohoSettingController::class, 'testConnection']);
            Route::post('/refresh-token', [ZohoSettingController::class, 'refreshAccessToken']);
        });

        // Dashboard
        Route::get('/dashboard/stats', [DashboardApiController::class, 'stats']);

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/stats', [AuditLogController::class, 'stats']);

        // API Providers
        Route::apiResource('providers', ApiProviderApiController::class);
        Route::post('/providers/{provider}/test', [ApiProviderApiController::class, 'testConnection']);

        // API Endpoints
        Route::apiResource('endpoints', ApiEndpointApiController::class);

        // Test API
        Route::post('/test/execute', [ApiTestApiController::class, 'execute']);
        Route::get('/providers/{provider}/endpoints', [ApiTestApiController::class, 'getEndpoints']);

        // User Management
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
