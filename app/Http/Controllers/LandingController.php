<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Serves the public marketing landing page.
 *
 * Only aggregate, non-sensitive counters are exposed: no vault content,
 * names, or credentials ever cross the public boundary.
 */
class LandingController extends Controller
{
    /**
     * Render the landing page with live aggregate statistics.
     *
     * @return View The landing view.
     */
    public function index(): View
    {
        $vaultItems = (int) VaultItem::count();
        $users = (int) User::count();
        $generated = (int) AuditLog::where('action_type', 'password_generated')->count();

        return view('landing', [
            'stats' => [
                'users' => $users,
                'passwordsStored' => $vaultItems,
                'passwordsGenerated' => $generated,
                'categories' => (int) DB::table('categories')->count(),
                'twoFactorUsers' => (int) DB::table('users')
                    ->whereNotNull('totp_secret')
                    ->where('is_totp_complete', true)
                    ->count(),
                'auditEvents' => (int) AuditLog::count(),
            ],
        ]);
    }
}
