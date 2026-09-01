<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-enforced session lifespan (SRS FR-8).
 *
 * Authenticated sessions carry an `expires_at` stamped at login:
 * 24 hours by default, 7 days when the user opted into "remember me".
 * Once the deadline passes the session is invalidated server-side and
 * the user is sent back to the login screen.
 *
 * Guest sessions have no `expires_at` (null) and are left untouched —
 * the framework's GC handles those via `session.lifetime`.
 */
class SessionLifespan
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The current request.
     * @param  Closure  $next  The next middleware.
     * @return Response The response or a login redirect.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();

        $row = DB::table('sessions')->where('id', $session->getId())->first();

        if ($row !== null && $row->expires_at !== null && Carbon::parse($row->expires_at)->isPast()) {
            DB::table('sessions')->where('id', $row->id)->delete();
            $session->invalidate();

            return redirect()->route('login')->with('status', __('Session expired. Please sign in again.'));
        }

        return $next($request);
    }
}