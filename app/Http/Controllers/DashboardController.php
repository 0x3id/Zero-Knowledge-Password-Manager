<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the user statistics dashboard.
     *
     * TOTP is the mandatory second factor: users who have not completed
     * enrollment are forced to the setup page before any private page.
     * The dashboard only aggregates metadata that is privacy-safe for
     * the owner: entry counts, activity volumes, session and passkey
     * counts. No vault plaintext ever reaches the server, so none of
     * these numbers can leak credential content.
     *
     * @param  Request  $request  The current request.
     * @return View|RedirectResponse The dashboard or the TOTP setup page.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_totp_complete) {
            return redirect()->route('totp.setup');
        }

        $weekStart = Carbon::today()->subDays(6)->startOfDay();

        // Friendly display name: the account stores a single `username`
        // (no separate first/last name), so we derive a readable greeting.
        $displayName = ucfirst(trim(preg_replace('/[^A-Za-z0-9]+/', ' ', (string) $user->username)));

        // Last successful login for the contextual welcome line.
        $lastLogin = $user->auditLogs()
            ->where('action_type', 'login')
            ->latest('created_at')
            ->first();

        // Days since the master/auth secret was last rotated, if ever.
        $lastPasswordChange = $user->auditLogs()
            ->where('action_type', 'password_changed')
            ->latest('created_at')
            ->first();

        // All audit activity inside the rolling week, pulled once and
        // bucketed for the chart (password generation) and the counts
        // (generated_week / audit_week) — one query instead of two.
        $weekLogs = $user->auditLogs()
            ->where('created_at', '>=', $weekStart)
            ->get(['action_type', 'created_at']);
        $generatedLogs = $weekLogs->where('action_type', 'password_generated');

        $vaultLogs = $user->vaultItems()
            ->where('created_at', '>=', $weekStart)
            ->pluck('created_at');

        $itemsByDay = $vaultLogs->countBy(fn (Carbon $at) => $at->format('Y-m-d'));
        $generatedByDay = $generatedLogs->pluck('created_at')->countBy(fn (Carbon $at) => $at->format('Y-m-d'));

        $chart = collect(range(6, 0))->map(function (int $offset) use ($itemsByDay, $generatedByDay) {
            $day = Carbon::today()->subDays($offset);
            $key = $day->format('Y-m-d');

            return [
                'label' => __('Days of the week '.$day->format('D')),
                'items' => (int) ($itemsByDay[$key] ?? 0),
                'generated' => (int) ($generatedByDay[$key] ?? 0),
            ];
        });
        $chartMax = max(1, $chart->max(fn (array $day) => $day['items'] + $day['generated']));

        // Derived once here: the passkey count (used for the dashboard card)
        // also tells us whether any passkey exists for the 2FA badge.
        $passkeyCount = $user->webauthnCredentials()->count();

        return view('dashboard', [
            'displayName' => $displayName,
            'lastLogin' => $lastLogin,
            'lastPasswordChange' => $lastPasswordChange,
            'counts' => [
                'vault_items' => $user->vaultItems()->count(),
                'categories' => $user->categories()->count(),
                'generated_total' => $user->auditLogs()->where('action_type', 'password_generated')->count(),
                'generated_week' => $generatedLogs->count(),
                'audit_week' => $weekLogs->count(),
                'sessions' => DB::table('sessions')->where('user_id', $user->id)->count(),
                'passkeys' => $passkeyCount,
                // The server stores only AES-256-GCM ciphertext and can never
                // inspect password contents, so a server-side breach/strength
                // score is not computable. Shown as 0 (nothing flagged); any
                // real weakness/breach detection happens client-side.
                'weak_passwords' => 0,
            ],
            'twoFactor' => [
                'totp' => (bool) $user->is_totp_complete,
                'webauthn' => $passkeyCount > 0,
            ],
            'chart' => $chart,
            'chartMax' => $chartMax,
            'recent' => $user->auditLogs()->latest()->limit(8)->get(),
            'memberSince' => $user->created_at,
        ]);
    }
}