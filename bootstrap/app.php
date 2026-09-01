<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // `zkpm_lang` is written client-side by the language switcher
        // (theme.js) and read by SetLocale; it must NOT be encrypted or
        // EncryptCookies silently drops it.
        $middleware->encryptCookies(except: ['zkpm_lang']);

        $middleware->web(append: [
            \App\Http\Middleware\ContentSecurityPolicy::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\NoCache::class,
            \App\Http\Middleware\SessionLifespan::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The zero-knowledge auth endpoints live under the web routes but
        // are consumed as JSON by the client crypto layer; honor the
        // request's Accept header so validation/abort errors render as
        // JSON 422/403 payloads instead of HTML redirects.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson(),
        );
    })->create();
