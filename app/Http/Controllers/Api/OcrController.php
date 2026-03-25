<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OcrFieldMapping;
use App\Models\OcrResult;
use App\Services\GoogleVisionService;
use App\Services\OcrExcelExportService;
use App\Services\OcrParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OcrController extends Controller
{
    public function __construct(
        private GoogleVisionService $visionService,
        private OcrParserService $parserService,
        private OcrExcelExportService $excelService,
    ) {}

    /**
     * Upload and process files with OCR.
     * Supports batch upload (multiple files) and multi-page PDF.
     *
     * POST /api/ocr/process
     */
    public function process(Request $request): StreamedResponse|JsonResponse
    {
        $request->validate([
            'files'            => 'required|array|min:1',
            'files.*'          => 'required|file|mimes:pdf,jpg,jpeg,png,tiff,tif,bmp,webp|max:51200',
            'field_mapping_id' => 'nullable|exists:ocr_field_mappings,id',
        ]);

        $batchId = Str::uuid()->toString();
        $fieldMapping = null;
        $fields = OcrParserService::defaultPassportFields();

        if ($request->filled('field_mapping_id')) {
            $fieldMapping = OcrFieldMapping::findOrFail($request->input('field_mapping_id'));
            $fields = $fieldMapping->fields;
        }

        $autoDetect = !$request->filled('field_mapping_id');
        $uploadedFiles = $request->file('files');

        // Copy files to temp storage before streaming (temp files may be cleaned up)
        $fileMetas = [];
        foreach ($uploadedFiles as $file) {
            $tmpPath = tempnam(sys_get_temp_dir(), 'ocr_');
            copy($file->getRealPath(), $tmpPath);
            $fileMetas[] = [
                'tmp_path'  => $tmpPath,
                'name'      => $file->getClientOriginalName(),
                'ext'       => $file->getClientOriginalExtension(),
                'mime'      => $file->getMimeType(),
            ];
        }

        $visionService = $this->visionService;
        $parserService = $this->parserService;

        return response()->stream(function () use (
            $batchId, $fieldMapping, $fields, $autoDetect,
            $fileMetas, $visionService, $parserService, $request
        ) {
            // Disable output buffering for real-time streaming
            while (ob_get_level()) ob_end_flush();

            $results = [];
            $totalFiles = count($fileMetas);

            foreach ($fileMetas as $fileIdx => $meta) {
                $originalName = $meta['name'];
                $fileExt      = $meta['ext'];
                $mimeType     = $meta['mime'];
                $tempPath     = $meta['tmp_path'];

                // Send: starting OCR for this file
                $this->sendEvent([
                    'event'     => 'file_start',
                    'file'      => $originalName,
                    'file_index' => $fileIdx + 1,
                    'file_total' => $totalFiles,
                ]);

                try {
                    $ocrData = $visionService->ocr($tempPath, $mimeType);

                    $pageTexts = $ocrData['page_texts'] ?? [$ocrData['text']];
                    $totalPages = $ocrData['pages'];
                    $pageConfidences = $ocrData['page_confidences'] ?? [];

                    // Send: OCR complete, starting page processing
                    $this->sendEvent([
                        'event'      => 'ocr_done',
                        'file'       => $originalName,
                        'total_pages' => $totalPages,
                    ]);

                    foreach ($pageTexts as $pageIndex => $pageText) {
                        $pageNum = $pageIndex + 1;

                        // Skip blank pages
                        if (mb_strlen(trim($pageText)) < 20) {
                            $this->sendEvent([
                                'event' => 'page_skip',
                                'file'  => $originalName,
                                'page'  => $pageNum,
                                'total' => $totalPages,
                                'reason' => 'blank',
                            ]);
                            continue;
                        }

                        // Determine template
                        $pageMapping = $fieldMapping;
                        $pageFields = $fields;
                        if ($autoDetect) {
                            $detected = $parserService->detectDocumentType($pageText);
                            if ($detected) {
                                $pageMapping = $detected;
                                $pageFields = $detected->fields;
                            } elseif (count($pageTexts) > 1) {
                                $this->sendEvent([
                                    'event' => 'page_skip',
                                    'file'  => $originalName,
                                    'page'  => $pageNum,
                                    'total' => $totalPages,
                                    'reason' => 'no_match',
                                ]);
                                continue;
                            }
                        }

                        // Landmark check for multi-page
                        if (count($pageTexts) > 1 && $pageMapping) {
                            $landmarks = $pageMapping->detection_landmarks ?? [];
                            if (!empty($landmarks)) {
                                $pageScore = $parserService->scoreLandmarks($pageText, $landmarks);
                                if ($pageScore < 50) {
                                    $this->sendEvent([
                                        'event' => 'page_skip',
                                        'file'  => $originalName,
                                        'page'  => $pageNum,
                                        'total' => $totalPages,
                                        'reason' => 'low_score',
                                    ]);
                                    continue;
                                }
                            }
                        }

                        $ocrResult = OcrResult::create([
                            'batch_id'          => $batchId,
                            'original_filename' => count($pageTexts) > 1
                                ? "{$originalName} (p.{$pageNum})"
                                : $originalName,
                            'file_type'         => $fileExt,
                            'page_count'        => $totalPages,
                            'page_number'       => $pageNum,
                            'field_mapping_id'  => $pageMapping?->id,
                            'status'            => 'processing',
                            'user_id'           => $request->user()?->id,
                        ]);

                        try {
                            $extractedData = $parserService->extract($pageText, $pageFields);
                            $confidence = $pageConfidences[$pageIndex] ?? null;

                            $ocrResult->update([
                                'raw_text'       => $pageText,
                                'extracted_data' => $extractedData,
                                'ocr_confidence' => $confidence,
                                'status'         => 'completed',
                            ]);
                        } catch (\Throwable $e) {
                            Log::error('OCR page processing failed', [
                                'file'  => $originalName,
                                'page'  => $pageNum,
                                'error' => $e->getMessage(),
                            ]);
                            $ocrResult->update([
                                'status'        => 'failed',
                                'error_message' => $e->getMessage(),
                            ]);
                        }

                        $freshResult = $ocrResult->fresh()->load('fieldMapping');
                        $results[] = $freshResult;

                        // Send: page done — include result data for real-time display
                        $this->sendEvent([
                            'event'  => 'page_done',
                            'file'   => $originalName,
                            'page'   => $pageNum,
                            'total'  => $totalPages,
                            'status' => $ocrResult->status,
                            'result' => $freshResult,
                        ]);
                    }

                } catch (\Throwable $e) {
                    Log::error('OCR file processing failed', [
                        'file'  => $originalName,
                        'error' => $e->getMessage(),
                    ]);

                    $ocrResult = OcrResult::create([
                        'batch_id'          => $batchId,
                        'original_filename' => $originalName,
                        'file_type'         => $fileExt,
                        'field_mapping_id'  => $fieldMapping?->id,
                        'status'            => 'failed',
                        'error_message'     => $e->getMessage(),
                        'user_id'           => $request->user()?->id,
                    ]);
                    $results[] = $ocrResult;

                    $this->sendEvent([
                        'event'  => 'file_error',
                        'file'   => $originalName,
                        'error'  => $e->getMessage(),
                        'result' => $ocrResult,
                    ]);
                } finally {
                    @unlink($tempPath);
                }
            }

            // Final event with all results
            $this->sendEvent([
                'event'         => 'complete',
                'message'       => count($results) . ' record(s) processed',
                'batch_id'      => $batchId,
                'template'      => $fieldMapping?->name ?? ($autoDetect ? 'Auto-detected' : 'Default (Passport Fields)'),
                'auto_detected' => $autoDetect,
                'results'       => $results,
            ]);

        }, 200, [
            'Content-Type'      => 'text/plain; charset=utf-8',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Send a single NDJSON event line and flush.
     */
    private function sendEvent(array $data): void
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        flush();
    }

    /**
     * Get OCR results for a batch.
     *
     * GET /api/ocr/batch/{batchId}
     */
    public function batch(string $batchId, Request $request): JsonResponse
    {
        $results = OcrResult::where('batch_id', $batchId)
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at')
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        return response()->json([
            'batch_id' => $batchId,
            'count'    => $results->count(),
            'results'  => $results,
        ]);
    }

    /**
     * Export a batch's OCR results to Excel.
     *
     * GET /api/ocr/batch/{batchId}/export
     */
    public function exportBatch(string $batchId, Request $request): StreamedResponse|JsonResponse
    {
        $results = OcrResult::where('batch_id', $batchId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No completed results found for this batch'], 404);
        }

        $filename = "ocr_batch_{$batchId}.xlsx";

        return $this->excelService->export($results, $filename);
    }

    /**
     * List all OCR results (paginated).
     *
     * GET /api/ocr/results
     */
    public function results(Request $request): JsonResponse
    {
        $query = OcrResult::with('fieldMapping')
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($batchFilter = $request->input('batch_id')) {
            $query->where('batch_id', $batchFilter);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('original_filename', 'like', "%{$search}%");
        }

        $results = $query->paginate($request->input('per_page', 20));

        return response()->json($results);
    }

    /**
     * Get a single OCR result.
     *
     * GET /api/ocr/results/{ocrResult}
     */
    public function showResult(OcrResult $ocrResult, Request $request): JsonResponse
    {
        abort_if($ocrResult->user_id !== $request->user()->id, 403, 'Access denied');
        $ocrResult->load('fieldMapping');

        return response()->json($ocrResult);
    }

    /**
     * Delete an OCR result.
     *
     * DELETE /api/ocr/results/{ocrResult}
     */
    public function destroyResult(OcrResult $ocrResult, Request $request): JsonResponse
    {
        abort_if($ocrResult->user_id !== $request->user()->id, 403, 'Access denied');
        $ocrResult->delete();

        return response()->json(['message' => 'ลบผล OCR เรียบร้อย']);
    }

    // ─── OCR Field Mapping CRUD ─────────────────────────────

    /**
     * List all field mappings.
     *
     * GET /api/ocr/field-mappings
     */
    public function fieldMappings(): JsonResponse
    {
        $mappings = OcrFieldMapping::orderBy('name')->get();

        return response()->json($mappings);
    }

    /**
     * Create a field mapping.
     *
     * POST /api/ocr/field-mappings
     */
    public function storeFieldMapping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'fields'            => 'required|array|min:1',
            'fields.*.key'      => 'required|string|max:100',
            'fields.*.label'    => 'required|string|max:255',
            'fields.*.keywords' => 'nullable|array',
            'fields.*.keywords.*' => 'string|max:255',
            'fields.*.regex'    => 'nullable|string|max:500',
            'fields.*.extraction_mode' => 'nullable|string|in:auto,same_line,next_line',
            'detection_landmarks'            => 'nullable|array',
            'detection_landmarks.*.type'     => 'required|string|in:mrz,keyword,regex,not_keyword',
            'detection_landmarks.*.value'    => 'nullable|string|max:500',
            'detection_landmarks.*.weight'   => 'required|integer|min:-200|max:200',
            'is_active'         => 'boolean',
        ]);

        $mapping = OcrFieldMapping::create($validated);

        return response()->json($mapping, 201);
    }

    /**
     * Update a field mapping.
     *
     * PUT /api/ocr/field-mappings/{ocrFieldMapping}
     */
    public function updateFieldMapping(Request $request, OcrFieldMapping $ocrFieldMapping): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'sometimes|required|string|max:255',
            'fields'            => 'sometimes|required|array|min:1',
            'fields.*.key'      => 'required|string|max:100',
            'fields.*.label'    => 'required|string|max:255',
            'fields.*.keywords' => 'nullable|array',
            'fields.*.keywords.*' => 'string|max:255',
            'fields.*.regex'    => 'nullable|string|max:500',
            'fields.*.extraction_mode' => 'nullable|string|in:auto,same_line,next_line',
            'detection_landmarks'            => 'nullable|array',
            'detection_landmarks.*.type'     => 'required|string|in:mrz,keyword,regex,not_keyword',
            'detection_landmarks.*.value'    => 'nullable|string|max:500',
            'detection_landmarks.*.weight'   => 'required|integer|min:-200|max:200',
            'is_active'         => 'boolean',
        ]);

        $ocrFieldMapping->update($validated);

        return response()->json($ocrFieldMapping);
    }

    /**
     * Delete a field mapping.
     *
     * DELETE /api/ocr/field-mappings/{ocrFieldMapping}
     */
    public function destroyFieldMapping(OcrFieldMapping $ocrFieldMapping): JsonResponse
    {
        $ocrFieldMapping->delete();

        return response()->json(['message' => 'Field mapping deleted']);
    }

    /**
     * Get the default passport field definitions.
     *
     * GET /api/ocr/default-fields
     */
    public function defaultFields(): JsonResponse
    {
        return response()->json([
            'fields' => OcrParserService::defaultPassportFields(),
        ]);
    }

    /**
     * Preview OCR on a sample file — extract raw text + auto-detect key-value pairs.
     * Used by the Smart Scan template builder.
     *
     * POST /api/ocr/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,tiff,tif,bmp,webp|max:51200',
        ]);

        $file = $request->file('file');

        try {
            $mimeType = $file->getMimeType();
            $tempPath = $file->getRealPath();

            // Perform OCR
            $ocrData = $this->visionService->ocr($tempPath, $mimeType);

            // Find best page (skip visa/noise pages for passports)
            $pageTexts = $ocrData['page_texts'] ?? [$ocrData['text']];
            $bestText = $this->parserService->findBestPage($pageTexts);

            // Auto-detect key-value pairs from best page
            $detectedPairs = $this->parserService->detectKeyValuePairs($bestText);

            return response()->json([
                'raw_text'       => $bestText,
                'page_count'     => $ocrData['pages'],
                'detected_pairs' => $detectedPairs,
            ]);
        } catch (\Throwable $e) {
            Log::error('OCR preview failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'OCR preview failed: ' . $e->getMessage(),
            ], 422);
        }
    }
}
