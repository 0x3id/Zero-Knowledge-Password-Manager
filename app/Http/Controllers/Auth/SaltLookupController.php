<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaltLookupController extends Controller
{
    /**
     * Return the KDF salt for a given email.
     *
     * Anti-enumeration: unknown emails receive a deterministic DECOY salt
     * (pseudo-random but stable per email + app key), so the response is
     * indistinguishable from that of a real account and repeated lookups
     * cannot leak whether an account exists.
     *
     * @param  Request  $request  The lookup request.
     * @return JsonResponse The salt payload consumed by the client KDF.
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        return response()->json([
            'kdf_salt' => $user?->kdf_salt ?? $this->decoySalt($validated['email']),
        ]);
    }

    /**
     * Derive a deterministic decoy salt for an unknown email.
     *
     * The salt is keyed by the application key, so it cannot be predicted
     * by outsiders, yet stays stable per email to keep responses
     * consistent across attempts.
     *
     * @param  string  $email  The unknown email address.
     * @return string The base64-encoded decoy salt.
     */
    private function decoySalt(string $email): string
    {
        return base64_encode(hash_hmac('sha256', 'decoy-salt:'.$email, (string) config('app.key'), true));
    }
}
