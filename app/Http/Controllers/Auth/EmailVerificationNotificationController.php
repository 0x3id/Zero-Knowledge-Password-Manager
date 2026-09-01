<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user?->hasVerifiedEmail()) {
            $destination = redirect()->intended(route('dashboard'));

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Email already verified.']);
            }

            return $destination;
        }

        $rateLimitKey = 'verification-resend:'.$user->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Too many resend attempts. Please try again later.',
                    'cooldown' => $seconds,
                ], 429);
            }

            return back()
                ->with('status', 'verification-rate-limited')
                ->with('cooldown', $seconds)
                ->withInput();
        }

        RateLimiter::hit($rateLimitKey, 60);

        \App\Jobs\SendVerificationEmail::dispatch($user, $user->preferredLocale());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Verification link sent.']);
        }

        return back()->with('status', 'verification-link-sent');
    }
}