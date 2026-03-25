<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\OcrResult;
use App\Models\ScanBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardApiController extends Controller
{
    public function stats(Request $request)
    {
        $userId = $request->user()->id;
        $now    = Carbon::now();
        $today  = $now->toDateString();
        $month  = $now->format('Y-m');

        // OCR counts (scoped to current user)
        $ocrTotal     = OcrResult::where('user_id', $userId)->count();
        $ocrToday     = OcrResult::where('user_id', $userId)->whereDate('created_at', $today)->count();
        $ocrSuccess   = OcrResult::where('user_id', $userId)->where('status', 'completed')->count();
        $ocrFailed    = OcrResult::where('user_id', $userId)->where('status', 'failed')->count();
        $ocrThisMonth = OcrResult::where('user_id', $userId)->where('created_at', 'like', "{$month}%")->count();

        // Batches (scoped to current user)
        $batchTotal = ScanBatch::where('user_id', $userId)->count();
        $batchToday = ScanBatch::where('user_id', $userId)->whereDate('created_at', $today)->count();

        // Labours (scoped to current user)
        $labourTotal = Labour::where('user_id', $userId)->count();

        // Expiry alerts (scoped to current user)
        $expiring30  = Labour::where('user_id', $userId)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $now->copy()->addDays(30)->toDateString()])
            ->count();
        $expiring60  = Labour::where('user_id', $userId)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $now->copy()->addDays(60)->toDateString()])
            ->count();
        $expired     = Labour::where('user_id', $userId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->count();

        return response()->json([
            'ocr' => [
                'total'      => $ocrTotal,
                'today'      => $ocrToday,
                'this_month' => $ocrThisMonth,
                'completed'  => $ocrSuccess,
                'failed'     => $ocrFailed,
            ],
            'batches' => [
                'total' => $batchTotal,
                'today' => $batchToday,
            ],
            'labours' => [
                'total'       => $labourTotal,
                'expiring_30' => $expiring30,
                'expiring_60' => $expiring60,
                'expired'     => $expired,
            ],
        ]);
    }
}
