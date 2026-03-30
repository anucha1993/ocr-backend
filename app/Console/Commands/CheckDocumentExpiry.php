<?php

namespace App\Console\Commands;

use App\Models\Labour;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckDocumentExpiry extends Command
{
    protected $signature = 'documents:check-expiry';
    protected $description = 'Check for expiring/expired documents and create notifications';

    public function handle(): int
    {
        $today = Carbon::today();
        $created = 0;

        // ── ❶ Already expired ──────────────────────────────────
        $expired = Labour::whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->where('expiry_date', '>=', $today->copy()->subDays(1)) // only yesterday's newly expired
            ->get();

        foreach ($expired as $labour) {
            $this->createNotification($labour, 'expired', 'เอกสารหมดอายุแล้ว',
                "เอกสารของ {$labour->firstname} {$labour->lastname} (Passport: {$labour->passport_no}) หมดอายุเมื่อ {$labour->expiry_date->format('d/m/Y')}");
            $created++;
        }

        // ── ❷ Expiring within 30 days ─────────────────────────
        $expiring30 = Labour::whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])
            ->get();

        foreach ($expiring30 as $labour) {
            $daysLeft = $today->diffInDays($labour->expiry_date);
            $this->createNotification($labour, 'expiry_critical', "เอกสารจะหมดอายุใน {$daysLeft} วัน",
                "เอกสารของ {$labour->firstname} {$labour->lastname} (Passport: {$labour->passport_no}) จะหมดอายุวันที่ {$labour->expiry_date->format('d/m/Y')} (เหลืออีก {$daysLeft} วัน)");
            $created++;
        }

        // ── ❸ Expiring within 31-60 days ──────────────────────
        $expiring60 = Labour::whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today->copy()->addDays(31), $today->copy()->addDays(60)])
            ->get();

        foreach ($expiring60 as $labour) {
            $daysLeft = $today->diffInDays($labour->expiry_date);
            $this->createNotification($labour, 'expiry_warning', "เอกสารจะหมดอายุใน {$daysLeft} วัน",
                "เอกสารของ {$labour->firstname} {$labour->lastname} (Passport: {$labour->passport_no}) จะหมดอายุวันที่ {$labour->expiry_date->format('d/m/Y')} (เหลืออีก {$daysLeft} วัน)");
            $created++;
        }

        $this->info("Created {$created} notifications.");

        return Command::SUCCESS;
    }

    private function createNotification(Labour $labour, string $type, string $title, string $message): void
    {
        // Skip if the same notification was already sent today
        $exists = Notification::where('user_id', $labour->user_id)
            ->where('entity_type', 'Labour')
            ->where('entity_id', $labour->id)
            ->where('type', $type)
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if ($exists) {
            return;
        }

        Notification::create([
            'user_id'     => $labour->user_id,
            'type'        => $type,
            'title'       => $title,
            'message'     => $message,
            'entity_type' => 'Labour',
            'entity_id'   => $labour->id,
        ]);
    }
}
