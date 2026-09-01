<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply the visitor's persisted UI locale to the current request.
 *
 * The language switcher stores the choice in a long-lived `zkpm_lang`
 * cookie (set client-side alongside a localStorage mirror), so Blade's
 * `__()` calls resolve through lang/{locale}.json and the layouts can
 * render the correct text direction (LTR/RTL). Arabic is the default
 * locale; English remains the fallback for anything unset or unsupported.
 */
class SetLocale
{
    /** Supported UI locales. */
    private const SUPPORTED = ['en', 'ar'];

    /**
     * Set the application locale from the request cookie.
     *
     * @param  Request  $request  The incoming request.
     * @param  Closure(Request): (Response)  $next  The next middleware handler.
     * @return Response The response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('zkpm_lang');

        if (is_string($locale) && in_array($locale, self::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
