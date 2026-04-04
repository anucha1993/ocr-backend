<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\ScanBatch;
use App\Services\AuditService;
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
        $user = $request->user();

        // Own batches + public batches from others
        $query = ScanBatch::withCount('labours')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('visibility', 'public');
            })
            ->latest();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($label = $request->input('label')) {
            $query->where('label', $label);
        }
        if ($request->input('mine_only')) {
            $query->where('user_id', $user->id);
        }

        $batches = $query->with('owner:id,name')->paginate($request->input('per_page', 20));

        return response()->json($batches);
    }

    public function show(ScanBatch $scanBatch, Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(
            $scanBatch->user_id !== $user->id && $scanBatch->visibility !== 'public',
            403,
            'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้'
        );
        $scanBatch->load('labours', 'owner:id,name');

        return response()->json($scanBatch);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'label'         => 'nullable|string|max:100',
            'note'          => 'nullable|string|max:1000',
            'visibility'    => 'nullable|in:private,public',
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
                'label'       => $validated['label'] ?? null,
                'note'        => $validated['note'] ?? null,
                'total_count' => count($validated['items']),
                'user_id'     => $request->user()->id,
                'visibility'  => $validated['visibility'] ?? 'private',
            ]);

            $vis = $validated['visibility'] ?? 'private';
            foreach ($validated['items'] as $item) {
                $item['batch_id']   = $batch->id;
                $item['user_id']    = $request->user()->id;
                $item['visibility'] = $vis;

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

            AuditService::logCreated($batch);

            return response()->json([
                'message' => "บันทึกชุด \"{$batch->name}\" สำเร็จ ({$batch->total_count} รายการ)",
                'batch'   => $batch,
            ], 201);
        });
    }

    public function updateVisibility(ScanBatch $scanBatch, Request $request): JsonResponse
    {
        abort_if($scanBatch->user_id !== $request->user()->id, 403, 'ไม่มีสิทธิ์แก้ไขข้อมูลนี้');

        $request->validate([
            'visibility' => 'required|in:private,public',
        ]);

        $scanBatch->update(['visibility' => $request->input('visibility')]);
        // Sync labours visibility
        $scanBatch->labours()->update(['visibility' => $request->input('visibility')]);

        return response()->json([
            'message'    => 'อัปเดตสิทธิ์เรียบร้อย',
            'visibility' => $scanBatch->visibility,
        ]);
    }

    public function destroy(ScanBatch $scanBatch, Request $request): JsonResponse
    {
        abort_if($scanBatch->user_id !== $request->user()->id, 403, 'ไม่มีสิทธิ์ลบข้อมูลนี้');
        AuditService::logDeleted($scanBatch);
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
        $headers = ['#', 'ประเภท', 'เลข Passport', 'ชื่อ', 'นามสกุล', 'สัญชาติ', 'เพศ', 'วันเกิด', 'สถานที่ออก', 'วันออกเอกสาร', 'วันหมดอายุ'];
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
            $docLabel = match(strtoupper($labour->document_type ?? '')) {
                'PJ' => 'PJ (Passport)',
                'CI' => 'CI (บัตร ปชช.)',
                'P', 'PASSPORT' => 'Passport',
                default => $labour->document_type ?? '',
            };
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $docLabel);
            $sheet->setCellValueExplicit("C{$row}", $labour->passport_no ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$row}", $labour->firstname);
            $sheet->setCellValue("E{$row}", $labour->lastname);
            $sheet->setCellValue("F{$row}", $labour->nationality ?? '');
            $sheet->setCellValue("G{$row}", $labour->gender ?? '');
            $sheet->setCellValue("H{$row}", $labour->birthdate?->format('d/m/Y') ?? '');
            $sheet->setCellValue("I{$row}", $labour->issue_place ?? '');
            $sheet->setCellValue("J{$row}", $labour->issue_date?->format('d/m/Y') ?? '');
            $sheet->setCellValue("K{$row}", $labour->expiry_date?->format('d/m/Y') ?? '');
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
