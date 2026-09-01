<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces strict Content-Security-Policy (CSP) and defense-in-depth security headers.
 *
 * Implements security hardening per ARCHITECTURE.md section 7:
 * - Restricts script execution to trusted origins ('self', fonts.bunny.net)
 *   and a SINGLE per-request nonce. Every inline <script> in the Blade
 *   templates carries `nonce="{{ $cspNonce }}"`, so 'unsafe-inline' is not
 *   needed for script execution.
 * - Restricts connections exclusively to 'self' and the k-Anonymity breach
 *   check API (api.pwnedpasswords.com).
 * - Restricts frame embedding (X-Frame-Options: DENY, frame-ancestors: 'none')
 *   to prevent clickjacking.
 * - Prevents MIME-type sniffing (X-Content-Type-Options: nosniff).
 * - Restricts referrer leakage (Referrer-Policy: strict-origin-when-cross-origin).
 */
class ContentSecurityPolicy
{
    /**
     * Handle an incoming request and apply strict security headers to the response.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Closure(Request): (Response)  $next  The next middleware pipeline handler.
     * @return Response The HTTP response with security headers attached.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // A fresh nonce per request scopes inline scripts to this response
        // only; a static value would let an attacker reuse a captured nonce.
        $nonce = base64_encode(random_bytes(18));
        $request->attributes->set('zkpm_csp_nonce', $nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        // Define strict Content-Security-Policy directives
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-eval' 'nonce-{$nonce}' https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net data:",
            "img-src 'self' data: blob:",
            "connect-src 'self' https://api.pwnedpasswords.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        // Apply Content-Security-Policy header
        $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives));

        // Anti-clickjacking protection
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer policy to prevent leakage of sensitive URL paths
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions policy restricting sensitive hardware access
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        return $response;
    }
}
