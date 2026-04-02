<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class GoogleVisionService
{
    private string $apiKey;
    private string $endpoint;

    /**
     * Max raw file size we can safely base64-encode and send inline.
     * Google Vision limit = 41,943,040 bytes payload.
     * Base64 overhead ~33%, so 30 MB raw ≈ 40 MB encoded.
     */
    private const PDF_INLINE_LIMIT = 30 * 1024 * 1024;

    public function __construct()
    {
        $this->apiKey = config('services.google_vision.api_key') ?? '';
        $this->endpoint = 'https://vision.googleapis.com/v1/images:annotate';
    }

    /**
     * Perform OCR on an image file (jpg, png, etc.) using DOCUMENT_TEXT_DETECTION.
     *
     * @param string $filePath Absolute path to the image file.
     * @return array{text: string, pages: int}
     */
    public function ocrImage(string $filePath): array
    {
        $imageContent = base64_encode(file_get_contents($filePath));

        $response = Http::timeout(120)->post("{$this->endpoint}?key={$this->apiKey}", [
            'requests' => [
                [
                    'image' => [
                        'content' => $imageContent,
                    ],
                    'features' => [
                        ['type' => 'DOCUMENT_TEXT_DETECTION'],
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            Log::error('Google Vision API error (image)', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Google Vision API request failed: ' . $response->body());
        }

        $data = $response->json();
        $text = $data['responses'][0]['fullTextAnnotation']['text'] ?? '';
        $pages = $data['responses'][0]['fullTextAnnotation']['pages'] ?? [];
        $confidence = $this->extractPageConfidence($pages[0] ?? []);

        return [
            'text'             => $text,
            'pages'            => 1,
            'page_texts'       => [$text],
            'page_confidences' => [$confidence],
        ];
    }

    /**
     * Perform OCR on a PDF file (supports multi-page) using DOCUMENT_TEXT_DETECTION.
     * Automatically splits large PDFs (>30 MB) into smaller chunks before sending.
     */
    public function ocrPdf(string $filePath): array
    {
        $fileSize = filesize($filePath);

        if ($fileSize > self::PDF_INLINE_LIMIT) {
            return $this->ocrLargePdf($filePath);
        }

        return $this->ocrSmallPdf($filePath);
    }

    /**
     * OCR for PDFs that fit within the 40 MB payload limit (original approach).
     */
    private function ocrSmallPdf(string $filePath): array
    {
        $pdfContent = base64_encode(file_get_contents($filePath));
        $filesEndpoint = 'https://vision.googleapis.com/v1/files:annotate';

        $pageTexts = [];
        $pageConfidences = [];
        $maxPagesPerRequest = 5;
        $maxPages = 100;

        // Batch pages in groups of 5 (API limit per request)
        for ($startPage = 1; $startPage <= $maxPages; $startPage += $maxPagesPerRequest) {
            $endPage = min($startPage + $maxPagesPerRequest - 1, $maxPages);
            $pages = range($startPage, $endPage);

            $response = Http::timeout(300)->post("{$filesEndpoint}?key={$this->apiKey}", [
                'requests' => [
                    [
                        'inputConfig' => [
                            'content'  => $pdfContent,
                            'mimeType' => 'application/pdf',
                        ],
                        'features' => [
                            ['type' => 'DOCUMENT_TEXT_DETECTION'],
                        ],
                        'pages' => $pages,
                    ],
                ],
            ]);

            if ($response->failed()) {
                if ($startPage === 1) {
                    Log::error('Google Vision API error (PDF)', [
                        'status' => $response->status(),
                    ]);
                    throw new \RuntimeException('Google Vision API request failed: ' . $response->body());
                }
                break;
            }

            $data = $response->json();

            if (isset($data['responses'][0]['error'])) {
                if ($startPage === 1) {
                    throw new \RuntimeException('Google Vision API error: ' . ($data['responses'][0]['error']['message'] ?? 'Unknown'));
                }
                break;
            }

            $batchResponses = $data['responses'][0]['responses'] ?? [];
            $batchHadText = false;

            foreach ($batchResponses as $pageResponse) {
                $pageText = $pageResponse['fullTextAnnotation']['text'] ?? '';
                if ($pageText !== '') {
                    $pageTexts[] = $pageText;
                    $pages = $pageResponse['fullTextAnnotation']['pages'] ?? [];
                    $pageConfidences[] = $this->extractPageConfidence($pages[0] ?? []);
                    $batchHadText = true;
                }
            }

            // Fallback for single-page structure (first batch only)
            if (!$batchHadText && $startPage === 1) {
                $fallbackText = $data['responses'][0]['fullTextAnnotation']['text'] ?? '';
                if ($fallbackText !== '') {
                    $pageTexts[] = $fallbackText;
                    $pages = $data['responses'][0]['fullTextAnnotation']['pages'] ?? [];
                    $pageConfidences[] = $this->extractPageConfidence($pages[0] ?? []);
                }
            }

            if (!$batchHadText || count($batchResponses) < $maxPagesPerRequest) {
                break;
            }
        }

        $allText = implode("\n\n", $pageTexts);

        return [
            'text'             => trim($allText),
            'pages'            => max(count($pageTexts), 1),
            'page_texts'       => $pageTexts,
            'page_confidences' => $pageConfidences,
        ];
    }

    /**
     * OCR for large PDFs: split into 5-page chunks using FPDI,
     * falling back to Imagick page-to-image if FPDI fails.
     */
    private function ocrLargePdf(string $filePath): array
    {
        Log::info('Large PDF detected, splitting before OCR', [
            'size_mb' => round(filesize($filePath) / 1024 / 1024, 1),
        ]);

        $pageTexts = [];
        $pageConfidences = [];
        $maxPagesPerChunk = 5;
        $maxPages = 100;

        // --- Strategy 1: Split with FPDI ---
        try {
            $totalPages = $this->getPdfPageCount($filePath);
            $totalPages = min($totalPages, $maxPages);

            for ($startPage = 1; $startPage <= $totalPages; $startPage += $maxPagesPerChunk) {
                $endPage = min($startPage + $maxPagesPerChunk - 1, $totalPages);

                $chunkPath = $this->extractPdfPages($filePath, $startPage, $endPage);

                try {
                    $chunkTexts = $this->ocrSmallPdf($chunkPath);
                    foreach ($chunkTexts['page_texts'] as $text) {
                        $pageTexts[] = $text;
                    }
                    foreach ($chunkTexts['page_confidences'] ?? [] as $conf) {
                        $pageConfidences[] = $conf;
                    }
                } finally {
                    @unlink($chunkPath);
                }
            }

            $allText = implode("\n\n", $pageTexts);

            return [
                'text'             => trim($allText),
                'pages'            => max(count($pageTexts), 1),
                'page_texts'       => $pageTexts,
                'page_confidences' => $pageConfidences,
            ];
        } catch (\Throwable $fpdiError) {
            Log::warning('FPDI split failed, trying Imagick fallback', [
                'error' => $fpdiError->getMessage(),
            ]);
        }

        // --- Strategy 2: Imagick page-to-image fallback ---
        if (extension_loaded('imagick')) {
            return $this->ocrPdfViaImagick($filePath, $maxPages);
        }

        throw new \RuntimeException(
            'PDF file is too large (' . round(filesize($filePath) / 1024 / 1024, 1)
            . ' MB). Maximum ~30 MB for inline processing. Please split the PDF or reduce scan resolution.'
        );
    }

    /**
     * Count pages in a PDF using FPDI.
     */
    private function getPdfPageCount(string $filePath): int
    {
        $fpdi = new Fpdi();
        return $fpdi->setSourceFile($filePath);
    }

    /**
     * Extract a range of pages from a PDF into a new temporary PDF file.
     */
    private function extractPdfPages(string $filePath, int $startPage, int $endPage): string
    {
        $fpdi = new Fpdi();
        $fpdi->setSourceFile($filePath);

        for ($page = $startPage; $page <= $endPage; $page++) {
            $templateId = $fpdi->importPage($page);
            $size = $fpdi->getTemplateSize($templateId);

            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($templateId);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'ocr_chunk_') . '.pdf';
        $fpdi->Output($tmpFile, 'F');

        return $tmpFile;
    }

    /**
     * Fallback: convert each PDF page to an image using Imagick, then OCR via images:annotate.
     */
    private function ocrPdfViaImagick(string $filePath, int $maxPages): array
    {
        Log::info('Using Imagick fallback for large PDF OCR');

        $imagick = new \Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($filePath);

        $totalPages = min($imagick->getNumberImages(), $maxPages);
        $pageTexts = [];
        $pageConfidences = [];

        for ($i = 0; $i < $totalPages; $i++) {
            $imagick->setIteratorIndex($i);
            $imagick->setImageFormat('png');
            $imageBlob = $imagick->getImageBlob();
            $imageContent = base64_encode($imageBlob);

            // Skip if single page image is somehow > 10 MB (images:annotate limit)
            if (strlen($imageContent) > 10 * 1024 * 1024) {
                Log::warning("Imagick page {$i} too large, skipping");
                continue;
            }

            try {
                $response = Http::timeout(120)->post("{$this->endpoint}?key={$this->apiKey}", [
                    'requests' => [
                        [
                            'image'    => ['content' => $imageContent],
                            'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
                        ],
                    ],
                ]);

                if ($response->ok()) {
                    $respData = $response->json();
                    $text = $respData['responses'][0]['fullTextAnnotation']['text'] ?? '';
                    if ($text !== '') {
                        $pageTexts[] = $text;
                        $vPages = $respData['responses'][0]['fullTextAnnotation']['pages'] ?? [];
                        $pageConfidences[] = $this->extractPageConfidence($vPages[0] ?? []);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Imagick OCR page {$i} failed", ['error' => $e->getMessage()]);
            }
        }

        $imagick->clear();
        $imagick->destroy();

        $allText = implode("\n\n", $pageTexts);

        return [
            'text'             => trim($allText),
            'pages'            => max(count($pageTexts), 1),
            'page_texts'       => $pageTexts,
            'page_confidences' => $pageConfidences,
        ];
    }

    /**
     * Extract average confidence score from a single page annotation.
     * Uses block-level confidence values from Google Vision's fullTextAnnotation.
     * Returns a float 0.0–1.0, or null if no confidence data is available.
     */
    private function extractPageConfidence(array $pageAnnotation): ?float
    {
        // Prefer the top-level page confidence if available
        if (isset($pageAnnotation['confidence']) && $pageAnnotation['confidence'] > 0) {
            return (float) $pageAnnotation['confidence'];
        }

        // Fall back to averaging block confidences
        $blocks = $pageAnnotation['blocks'] ?? [];
        if (empty($blocks)) {
            return null;
        }

        $confidences = [];
        foreach ($blocks as $block) {
            if (isset($block['confidence'])) {
                $confidences[] = (float) $block['confidence'];
            }
        }

        if (empty($confidences)) {
            return null;
        }

        return round(array_sum($confidences) / count($confidences), 4);
    }

    /**
     * Auto-detect file type and perform OCR.
     *
     * @param string $filePath Absolute path to the file.
     * @param string $mimeType MIME type of the file.
     * @return array{text: string, pages: int}
     */
    public function ocr(string $filePath, string $mimeType): array
    {
        if ($mimeType === 'application/pdf') {
            return $this->ocrPdf($filePath);
        }

        return $this->ocrImage($filePath);
    }

    /**
     * OCR a PDF page-by-page, calling $onPage after each page is done.
     * This enables real-time streaming of results.
     *
     * @param string   $filePath  Path to the PDF.
     * @param callable $onPage    fn(int $pageNum, int $totalPages, string $text, ?float $confidence): void
     */
    public function ocrPdfPageByPage(string $filePath, callable $onPage): void
    {
        $totalPages = $this->getPdfPageCount($filePath);
        $totalPages = min($totalPages, 100);

        for ($page = 1; $page <= $totalPages; $page++) {
            $chunkPath = $this->extractPdfPages($filePath, $page, $page);
            try {
                $result = $this->ocrSmallPdf($chunkPath);
                $text       = $result['page_texts'][0] ?? '';
                $confidence = $result['page_confidences'][0] ?? null;
                $onPage($page, $totalPages, $text, $confidence);
            } catch (\Throwable $e) {
                Log::warning("ocrPdfPageByPage: page {$page} failed", ['error' => $e->getMessage()]);
                $onPage($page, $totalPages, '', null);
            } finally {
                @unlink($chunkPath);
            }
        }
    }

    /**
     * Get the number of pages in a PDF (public wrapper).
     */
    public function pdfPageCount(string $filePath): int
    {
        return $this->getPdfPageCount($filePath);
    }
}
