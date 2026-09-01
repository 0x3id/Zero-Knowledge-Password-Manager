<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __invoke(Request $request, AuditLogger $audit): RedirectResponse|View
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $tokenHash = $request->route('hash');

        $token = DB::table('email_verification_tokens')
            ->where('token_hash', $tokenHash)
            ->where('user_id', $user->id)
            ->first();

        if (! $token) {
            return redirect()->route('verification.notice');
        }

        if ($token->used_at) {
            return view('auth.verification-already-verified', [
                'email' => $user->email,
            ]);
        }

        if (Carbon::parse($token->expires_at)->isPast()) {
            return redirect()->route('verification.notice');
        }

        DB::table('email_verification_tokens')
            ->where('id', $token->id)
            ->update(['used_at' => now()]);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $audit->log($user, 'email_verified', $request);
        }

        return redirect()->route('totp.setup');
    }
}