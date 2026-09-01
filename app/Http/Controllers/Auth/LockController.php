<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LockController extends Controller
{
    /**
     * Serve the client-side vault-lock context.
     *
     * Returns the `vault_canary` ciphertext (used by the client to verify
     * a freshly derived key without touching real vault data) and whether
     * the user has any WebAuthn credential, so the lock modal can decide
     * whether the biometric unlock path is available.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The lock-state payload.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'vault_canary' => $user->vault_canary,
            'has_webauthn' => $user->webauthnCredentials()->exists(),
        ]);
    }
}
