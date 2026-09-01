<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WebAuthnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WebauthnAuthenticationController extends Controller
{
    /**
     * Generate WebAuthn assertion options.
     *
     * Serves two flows: factor-2 login (a user parked in a pending 2FA
     * session) and client-side vault unlock (an already-authenticated
     * user whose in-memory key was cleared by auto-lock). The route is
     * therefore NOT behind the guest or auth middleware — access is
     * gated here by resolving either context.
     *
     * @param  Request  $request  The current request.
     * @param  WebAuthnService  $webAuthn  The WebAuthn verifier.
     * @return JsonResponse The PublicKeyCredentialRequestOptions payload.
     */
    public function options(Request $request, WebAuthnService $webAuthn): JsonResponse
    {
        $user = $this->resolvedUser($request);

        if ($user === null) {
            return response()->json(['message' => 'No pending login.'], 403);
        }

        return response()->json($webAuthn->createAssertionOptions($user));
    }

    /**
     * Verify a WebAuthn assertion and complete the appropriate flow.
     *
     * For factor-2 login the login is completed and the session row and
     * audit entry are written. For vault unlock the credential counter is
     * updated server-side and the client resumes by decrypting its
     * per-device IndexedDB blob locally.
     *
     * @param  Request  $request  The current request.
     * @param  WebAuthnService  $webAuthn  The WebAuthn verifier.
     * @return JsonResponse The outcome including the redirect target when logging in.
     */
    public function verify(Request $request, WebAuthnService $webAuthn): JsonResponse
    {
        $user = $this->resolvedUser($request);

        if ($user === null) {
            return response()->json(['message' => 'No pending login.'], 403);
        }

        $validated = $request->validate([
            'auth_data' => ['required', 'string'],
            'client_data_json' => ['required', 'string'],
            'signature' => ['required', 'string'],
            'raw_id' => ['required', 'string'],
        ]);

        try {
            $webAuthn->verifyAssertion(
                $user,
                $validated['auth_data'],
                $validated['client_data_json'],
                $validated['signature'],
                $validated['raw_id'],
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($request->session()->get(AuthenticatedSessionController::PENDING_USER) !== null) {
            $redirect = AuthenticatedSessionController::completeTwoFactorLogin($user, $request)->getTargetUrl();

            return response()->json(['ok' => true, 'redirect' => $redirect]);
        }

        // Vault unlock: nothing further server-side; the client restores
        // the key from its per-device IndexedDB blob.
        return response()->json(['ok' => true]);
    }

    /**
     * Resolve the acting user: the pending 2FA user first, then the
     * authenticated user (vault unlock), otherwise null.
     *
     * @param  Request  $request  The current request.
     * @return User|null The resolved user, or null.
     */
    private function resolvedUser(Request $request): ?User
    {
        $userId = $request->session()->get(AuthenticatedSessionController::PENDING_USER);

        if ($userId !== null) {
            return User::find($userId);
        }

        return $request->user();
    }
}
