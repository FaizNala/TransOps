<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query('filter', []);

        $logs = AuditLog::query()
            ->with('user')
            ->when($filters['entity_type'] ?? null, fn ($query, $type) => $query->where('entity_type', $type))
            ->when($filters['entity_id'] ?? null, fn ($query, $id) => $query->where('entity_id', $id))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'user' => $log->user?->name ?? 'Sistem',
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'before' => $log->before,
                'after' => $log->after,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
            ]);

        return inertia('audit-logs/index', [
            'logs' => $logs,
            'filters' => $request->query('filter', []),
            'entities' => AuditLog::query()
                ->select('entity_type')
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type')
                ->values(),
        ]);
    }
}
