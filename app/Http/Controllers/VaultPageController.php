<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VaultPageController extends Controller
{
    /**
     * Show the vault page.
     *
     * TOTP is the mandatory second factor: users who have not completed
     * enrollment are forced to the setup page before the vault is
     * accessible. The vault content area mounts the client-side lock
     * modal; the page is served regardless of vault-lock state because
     * the Laravel session remains valid — locking is a client-side
     * concern only.
     *
     * @param  Request  $request  The current request.
     * @return View|RedirectResponse The vault page or the TOTP setup page.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->is_totp_complete) {
            return redirect()->route('totp.setup');
        }

        return view('vault.page');
    }
}