<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display the audit log page or return JSON log records.
     *
     * In accordance with ARCHITECTURE.md and SRS FR-9, audit log records
     * contain strictly metadata (action type, IP address, device info, timestamp)
     * and NEVER contain any vault titles, usernames, passwords, or notes.
     *
     * @param  Request  $request  The incoming request.
     * @return View|JsonResponse The audit log view or JSON payload.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = AuditLog::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Optional filter by action type
        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        $logs = $query->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return view('audit-logs.index', [
            'logs' => $logs,
            'currentAction' => $request->input('action_type', ''),
        ]);
    }
}
