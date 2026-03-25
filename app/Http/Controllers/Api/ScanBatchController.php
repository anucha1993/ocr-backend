<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\ScanBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScanBatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ScanBatch::withCount('labours')
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $batches = $query->paginate($request->input('per_page', 20));

        return response()->json($batches);
    }

    public function show(ScanBatch $scanBatch, Request $request): JsonResponse
    {
        abort_if($scanBatch->user_id !== $request->user()->id, 403, 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้');
        $scanBatch->load('labours');

        return response()->json($scanBatch);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'note'          => 'nullable|string|max:1000',
            'items'         => 'required|array|min:1',
            'items.*.document_type' => 'nullable|string|in:idcard,passport',
            'items.*.id_card'       => 'nullable|string|max:20',
            'items.*.passport_no'   => 'nullable|string|max:20',
            'items.*.prefix'        => 'nullable|string|max:50',
            'items.*.firstname'     => 'required|string|max:255',
            'items.*.lastname'      => 'required|string|max:255',
            'items.*.birthdate'     => 'nullable|date',
            'items.*.address'       => 'nullable|string',
            'items.*.nationality'   => 'nullable|string|max:100',
            'items.*.issue_date'    => 'nullable|date',
            'items.*.expiry_date'   => 'nullable|date',
            'items.*.photo'         => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $batch = ScanBatch::create([
                'name'        => $validated['name'],
                'note'        => $validated['note'] ?? null,
                'total_count' => count($validated['items']),
                'user_id'     => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $item['batch_id'] = $batch->id;
                $item['user_id']  = $request->user()->id;

                if (!empty($item['passport_no'])) {
                    Labour::updateOrCreate(
                        ['passport_no' => $item['passport_no'], 'user_id' => $request->user()->id],
                        $item
                    );
                } elseif (!empty($item['id_card'])) {
                    Labour::updateOrCreate(
                        ['id_card' => $item['id_card'], 'user_id' => $request->user()->id],
                        $item
                    );
                } else {
                    Labour::create($item);
                }
            }

            $batch->load('labours');

            return response()->json([
                'message' => "บันทึกชุด \"{$batch->name}\" สำเร็จ ({$batch->total_count} รายการ)",
                'batch'   => $batch,
            ], 201);
        });
    }

    public function destroy(ScanBatch $scanBatch, Request $request): JsonResponse
    {
        abort_if($scanBatch->user_id !== $request->user()->id, 403, 'ไม่มีสิทธิ์ลบข้อมูลนี้');
        // Detach labours (set batch_id to null) then delete batch
        $scanBatch->labours()->update(['batch_id' => null]);
        $scanBatch->delete();

        return response()->json(['ผล' => 'ลบชุดสำเร็จ']);
    }

    public function export(ScanBatch $scanBatch, Request $request): StreamedResponse
    {
        abort_if($scanBatch->user_id !== $request->user()->id, 403, 'ไม่มีสิทธิ์ export ข้อมูลนี้');
        $scanBatch->load('labours');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Batch Export');

        // Header row
        $headers = ['#', 'ประเภท', 'เลข Passport', 'ชื่อ', 'นามสกุล', 'สัญชาติ', 'วันเกิด', 'วันออกเอกสาร', 'วันหมดอายุ'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Style header
        $lastCol = chr(ord('A') + count($headers) - 1);
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Data rows
        $row = 2;
        foreach ($scanBatch->labours as $i => $labour) {
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $labour->document_type === 'passport' ? 'Passport' : 'บัตร ปชช.');
            $sheet->setCellValueExplicit("C{$row}", $labour->passport_no ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$row}", $labour->firstname);
            $sheet->setCellValue("E{$row}", $labour->lastname);
            $sheet->setCellValue("F{$row}", $labour->nationality ?? '');
            $sheet->setCellValue("G{$row}", $labour->birthdate?->format('d/m/Y') ?? '');
            $sheet->setCellValue("H{$row}", $labour->issue_date?->format('d/m/Y') ?? '');
            $sheet->setCellValue("I{$row}", $labour->expiry_date?->format('d/m/Y') ?? '');
            $row++;
        }

        // Border all data
        $dataRange = "A1:{$lastCol}" . ($row - 1);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto-width columns
        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = str_replace([' ', '/'], '_', $scanBatch->name) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
