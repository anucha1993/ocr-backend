<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('entity_type', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        return response()->json(
            $query->paginate($request->input('per_page', 30))
        );
    }

    public function stats()
    {
        $today = now()->toDateString();

        return response()->json([
            'total'       => AuditLog::count(),
            'today'       => AuditLog::whereDate('created_at', $today)->count(),
            'actions'     => AuditLog::selectRaw('action, count(*) as count')
                                ->groupBy('action')
                                ->pluck('count', 'action'),
            'top_users'   => AuditLog::selectRaw('user_id, count(*) as count')
                                ->with('user:id,name')
                                ->groupBy('user_id')
                                ->orderByDesc('count')
                                ->limit(5)
                                ->get(),
        ]);
    }
}
