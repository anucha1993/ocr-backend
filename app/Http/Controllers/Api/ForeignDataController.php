<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExternalApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForeignDataController extends Controller
{
    public function __construct(
        private ExternalApiService $apiService,
    ) {}

    /**
     * List Foreign Data records from Zoho CRM.
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 20);

        $result = $this->apiService->callEndpoint(
            'zoho-crm',
            'Get Leads',
            ['page' => $page, 'per_page' => $perPage],
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to fetch records.',
            ], $result['status'] ?? 500);
        }

        $data = $result['data']['data'] ?? [];
        $info = $result['data']['info'] ?? [];

        return response()->json([
            'success' => true,
            'data' => $data,
            'info' => $info,
        ]);
    }

    /**
     * Get a single Foreign Data record by ID.
     */
    public function show(string $id): JsonResponse
    {
        $result = $this->apiService->callEndpoint(
            'zoho-crm',
            'Get Lead By ID',
            [],
            ['id' => $id],
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Record not found.',
            ], $result['status'] ?? 404);
        }

        $records = $result['data']['data'] ?? [];

        return response()->json([
            'success' => true,
            'data' => $records[0] ?? null,
        ]);
    }

    /**
     * Create a new Foreign Data record.
     */
    public function store(Request $request): JsonResponse
    {
        $result = $this->apiService->callEndpoint(
            'zoho-crm',
            'Create Lead',
            ['data' => [$request->all()]],
        );

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * Update a Foreign Data record.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $payload = $request->all();
        $payload['id'] = $id;

        $result = $this->apiService->callEndpoint(
            'zoho-crm',
            'Update Lead',
            ['data' => [$payload]],
            ['id' => $id],
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Delete a Foreign Data record.
     */
    public function destroy(string $id): JsonResponse
    {
        $result = $this->apiService->callEndpoint(
            'zoho-crm',
            'Delete Lead',
            [],
            ['id' => $id],
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Search Foreign Data by Passport ID.
     */
    public function searchByPassport(Request $request): JsonResponse
    {
        $passportNo = $request->input('passport_no');

        if (!$passportNo) {
            return response()->json(['success' => false, 'message' => 'passport_no is required.'], 422);
        }

        $result = $this->apiService->callEndpoint(
            'zoho-crm',
            'Search Lead',
            ['criteria' => "(Passport_ID:equals:{$passportNo})"],
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Search failed.',
            ], $result['status'] ?? 500);
        }

        $data = $result['data']['data'] ?? [];

        return response()->json([
            'success' => true,
            'data'    => $data,
            'count'   => count($data),
        ]);
    }
}
