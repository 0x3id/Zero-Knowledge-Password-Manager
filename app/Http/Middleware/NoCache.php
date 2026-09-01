<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoCache
{
    /**
     * Prevent heuristic/stale caching of application pages.
     *
     * The vault serves per-build hashed assets and inline client crypto
     * bootstrap; browsers that heuristically cache HTML pages (no explicit
     * Cache-Control) can end up executing deleted asset hashes, which
     * surfaces as "Unexpected token" script errors. Every web response is
     * therefore marked no-store.
     *
     * @param  Request  $request  The incoming request.
     * @param  Closure  $next  The next middleware.
     * @return Response The response with no-store cache headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}