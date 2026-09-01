<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SessionController extends Controller
{
    /**
     * Display the active sessions and device management page or JSON data.
     *
     * @param  Request  $request  The incoming request.
     * @return View|JsonResponse The sessions view or JSON data.
     */
    public function index(Request $request): View|JsonResponse
    {
        $currentSessionId = $request->session()->getId();
        $user = $request->user();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_active_at', 'desc')
            ->get()
            ->map(function (object $session) use ($currentSessionId): object {
                return (object) [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'device_name' => $session->device_name ?? 'Unknown Device',
                    'location' => $session->location ?? 'Unknown Location',
                    'is_remember_me' => (bool) $session->is_remember_me,
                    'last_active_at' => $session->last_active_at,
                    'expires_at' => $session->expires_at,
                    'is_current' => $session->id === $currentSessionId,
                ];
            });

        if ($request->wantsJson()) {
            return response()->json([
                'sessions' => $sessions,
                'current_session_id' => $currentSessionId,
            ]);
        }

        return view('sessions.index', [
            'sessions' => $sessions,
            'currentSessionId' => $currentSessionId,
        ]);
    }

    /**
     * Revoke a specific active session.
     *
     * @param  Request  $request  The incoming request.
     * @param  string  $sessionId  The session ID to terminate.
     * @param  AuditLogger  $audit  The audit logger.
     * @return JsonResponse|RedirectResponse The revocation status.
     */
    public function destroy(Request $request, string $sessionId, AuditLogger $audit): JsonResponse|RedirectResponse
    {
        $currentSessionId = $request->session()->getId();
        $user = $request->user();

        // Prevent revoking the current session via this endpoint (must use /logout)
        if ($sessionId === $currentSessionId) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Cannot revoke current session. Use logout instead.'], 422);
            }
            return back()->withErrors(['message' => 'Cannot revoke current session. Use logout instead.']);
        }

        $deleted = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();

        if ($deleted > 0) {
            $audit->log($user, 'session_revoked', $request);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'session-revoked');
    }

    /**
     * Revoke all other active sessions for the current user.
     *
     * @param  Request  $request  The incoming request.
     * @param  AuditLogger  $audit  The audit logger.
     * @return JsonResponse|RedirectResponse The revocation status.
     */
    public function destroyOthers(Request $request, AuditLogger $audit): JsonResponse|RedirectResponse
    {
        $currentSessionId = $request->session()->getId();
        $user = $request->user();

        $deleted = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        if ($deleted > 0) {
            $audit->log($user, 'session_revoked', $request);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'revoked_count' => $deleted]);
        }

        return back()->with('status', 'other-sessions-revoked');
    }
}
