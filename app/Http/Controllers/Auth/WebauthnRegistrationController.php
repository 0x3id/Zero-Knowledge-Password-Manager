<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WebauthnCredential;
use App\Services\AuditLogger;
use App\Services\WebAuthnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WebauthnRegistrationController extends Controller
{
    /**
     * List all registered WebAuthn credentials for the authenticated user.
     *
     * @param  Request  $request  The incoming request.
     * @return JsonResponse List of user credentials.
     */
    public function index(Request $request): JsonResponse
    {
        $credentials = $request->user()->webauthnCredentials()
            ->select(['id', 'device_label', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'credentials' => $credentials,
        ]);
    }

    /**
     * Generate WebAuthn credential creation options for registration.
     *
     * @param  Request  $request  The incoming request.
     * @param  WebAuthnService  $webAuthn  The WebAuthn service.
     * @return JsonResponse The PublicKeyCredentialCreationOptions.
     */
    public function options(Request $request, WebAuthnService $webAuthn): JsonResponse
    {
        $options = $webAuthn->createRegistrationOptions($request->user());

        return response()->json($options);
    }

    /**
     * Verify the authenticator attestation and persist the new credential.
     *
     * @param  Request  $request  The incoming request.
     * @param  WebAuthnService  $webAuthn  The WebAuthn service.
     * @param  AuditLogger  $audit  The audit logger.
     * @return JsonResponse The registration result.
     */
    public function verify(Request $request, WebAuthnService $webAuthn, AuditLogger $audit): JsonResponse
    {
        $validated = $request->validate([
            'client_data_json' => ['required', 'string'],
            'attestation_object' => ['required', 'string'],
            'raw_id' => ['required', 'string'],
            'device_label' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        try {
            $parsed = $webAuthn->verifyRegistration(
                $user,
                $validated['client_data_json'],
                $validated['attestation_object'],
                $validated['raw_id']
            );

            $deviceLabel = $validated['device_label']
                ?: AuditLogger::parseUserAgent((string) $request->userAgent());

            $credential = $user->webauthnCredentials()->create([
                'credential_id' => $parsed['credential_id'],
                'public_key' => $parsed['public_key'],
                'device_label' => $deviceLabel,
                'counter' => $parsed['counter'] ?? 0,
            ]);

            $audit->log($user, 'webauthn_registered', $request);

            return response()->json([
                'ok' => true,
                'credential' => [
                    'id' => $credential->id,
                    'device_label' => $credential->device_label,
                    'created_at' => $credential->created_at,
                ],
            ], 201);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    /**
     * Delete a registered WebAuthn credential.
     *
     * @param  Request  $request  The incoming request.
     * @param  WebauthnCredential  $credential  The credential to delete.
     * @param  AuditLogger  $audit  The audit logger.
     * @return JsonResponse The deletion status.
     */
    public function destroy(Request $request, WebauthnCredential $credential, AuditLogger $audit): JsonResponse
    {
        if ($credential->user_id !== $request->user()->id) {
            abort(404);
        }

        $credential->delete();

        $audit->log($request->user(), 'webauthn_removed', $request);

        return response()->json(['ok' => true]);
    }
}
