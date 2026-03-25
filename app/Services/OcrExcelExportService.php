<?php

namespace App\Services;

use App\Models\OcrResult;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OcrExcelExportService
{
    /**
     * Export OCR results to an Excel file.
     *
     * @param \Illuminate\Support\Collection<OcrResult> $results
     * @param string $filename
     * @return StreamedResponse
     */
    public function export($results, string $filename = 'ocr_export.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('OCR Results');

        // Collect all unique field keys across results
        $allKeys = $this->collectFieldKeys($results);

        // Build headers: static columns + dynamic field columns
        $headers = ['#', 'ไฟล์', 'ประเภท', 'หน้า', 'สถานะ', 'ความแม่นยำ (%)'];
        foreach ($allKeys as $key) {
            $headers[] = $this->keyToLabel($key);
        }

        // Write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Style headers
        $lastCol = chr(ord('A') + count($headers) - 1);
        if (count($headers) > 26) {
            // Handle more than 26 columns
            $lastCol = 'A' . chr(ord('A') + count($headers) - 27);
        }
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Write data rows
        $row = 2;
        $confidenceColIndex = 6; // F column (1-based)
        foreach ($results as $i => $result) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $i + 1);
            $sheet->setCellValue($col++ . $row, $result->original_filename);
            $sheet->setCellValue($col++ . $row, $result->file_type);
            $sheet->setCellValue($col++ . $row, $result->page_number ?? $result->page_count);
            $sheet->setCellValue($col++ . $row, ucfirst($result->status));

            // Confidence column: format as percentage with color
            $confidenceCol = $col++;
            $confidence = $result->ocr_confidence;
            if ($confidence !== null) {
                $pct = round($confidence * 100, 1);
                $sheet->setCellValue($confidenceCol . $row, $pct . '%');
                // Color code by accuracy
                if ($pct >= 95) {
                    $bgColor = 'D1FAE5'; // green
                    $textColor = '065F46';
                } elseif ($pct >= 80) {
                    $bgColor = 'FEF3C7'; // yellow
                    $textColor = '92400E';
                } else {
                    $bgColor = 'FEE2E2'; // red
                    $textColor = '991B1B';
                }
                $sheet->getStyle($confidenceCol . $row)->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                    'font'      => ['bold' => true, 'color' => ['rgb' => $textColor]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            } else {
                $sheet->setCellValue($confidenceCol . $row, '-');
                $sheet->getStyle($confidenceCol . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $extractedData = $result->extracted_data ?? [];
            foreach ($allKeys as $key) {
                $sheet->setCellValue($col++ . $row, $extractedData[$key] ?? '');
            }

            $row++;
        }

        // Border all data
        if ($row > 2) {
            $dataRange = "A1:{$lastCol}" . ($row - 1);
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        // Auto-width columns
        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Collect all unique field keys from results' extracted_data.
     */
    private function collectFieldKeys($results): array
    {
        $keys = [];

        foreach ($results as $result) {
            if (is_array($result->extracted_data)) {
                foreach (array_keys($result->extracted_data) as $key) {
                    if (!in_array($key, $keys, true)) {
                        $keys[] = $key;
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * Convert a snake_case key to a human-readable label.
     */
    private function keyToLabel(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }
}
