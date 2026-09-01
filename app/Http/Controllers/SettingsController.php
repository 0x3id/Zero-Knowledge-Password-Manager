<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\VaultItem;
use App\Models\WebauthnCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the Security Center (settings hub) page with a live snapshot
     * of the user's vault posture, authentication methods, and devices.
     *
     * Aggregates only non-sensitive metadata: item/category counts, 2FA
     * enrollment state, passkey count, active session count, and audit
     * log volume. No vault titles, URLs, or ciphertexts are exposed.
     *
     * @param  Request  $request  The incoming request.
     * @return View|JsonResponse The settings hub view or JSON payload.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        $activeSessions = DB::table('sessions')->where('user_id', $user->id)->count();
        $vaultItemCount = VaultItem::where('user_id', $user->id)->count();
        $categoryCount = $user->categories()->count();
        $passkeyCount = WebauthnCredential::where('user_id', $user->id)->count();
        $auditLogCount = AuditLog::where('user_id', $user->id)->count();

        $overview = [
            'vault_item_count' => $vaultItemCount,
            'category_count' => $categoryCount,
            'is_totp_complete' => (bool) $user->is_totp_complete,
            'passkey_count' => $passkeyCount,
            'active_sessions' => $activeSessions,
            'audit_log_count' => $auditLogCount,
        ];

        if ($request->wantsJson()) {
            return response()->json($overview);
        }

        return view('settings.index', $overview);
    }
}