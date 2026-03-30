<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        if ($request->input('unread_only')) {
            $query->unread();
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json(
            $query->paginate($request->input('per_page', 20))
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markAsRead(Notification $notification, Request $request): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'อ่านแล้ว']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'อ่านทั้งหมดแล้ว']);
    }

    public function destroy(Notification $notification, Request $request): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->delete();

        return response()->json(['message' => 'ลบการแจ้งเตือนสำเร็จ']);
    }
}
