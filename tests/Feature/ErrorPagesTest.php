<?php

use App\Models\User;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;

/*
 * The custom error views in resources/views/errors/ are resolved automatically
 * by Laravel's exception handler (`errors::<status>` namespace), so no
 * exception-handler wiring is required. These tests prove each view is
 * actually served for its status code, respects the theme/bootstrap shell,
 * honours the auth state for its primary CTA, and — on the 500 page — never
 * leaks the underlying exception.
 */

beforeEach(function () {
    // The app defaults to Arabic; pin English so the assertions are readable.
    app()->setLocale('en');
    Lang::setLocale('en');
    config(['app.locale' => 'en']);

    Route::middleware('web')->group(function (): void {
        Route::get('/__err/401', fn () => abort(401));
        Route::get('/__err/403', fn () => abort(403));
        Route::get('/__err/404', fn () => abort(404));
        Route::get('/__err/419', fn () => abort(419));
        Route::get('/__err/429', fn () => abort(429, 'Too Many Requests', ['Retry-After' => 45]));
        Route::get('/__err/500', fn () => abort(500));
        Route::get('/__err/503', fn () => abort(503, 'Service Unavailable', ['Retry-After' => 300]));
    });
});

it('serves the custom 404 view for unknown paths', function () {
    $this->get('/__err/404')
        ->assertStatus(404)
        ->assertSee('This vault door doesn’t exist.')
        ->assertSee('Vault index', false)
        ->assertSee('cipher-strip-line', false);
});

it('serves the custom 401 view with a login path for guests', function () {
    $this->get('/__err/401')
        ->assertStatus(401)
        ->assertSee('This vault is sealed to you.')
        ->assertSee('href="' . route('login') . '"', false);
});

it('serves the custom 403 view with a dashboard path for signed-in users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/__err/403')
        ->assertStatus(403)
        ->assertSee('This vault is sealed to you.')
        ->assertSee('href="' . route('dashboard') . '"', false);
});

it('serves the custom 419 view for the CSRF/expired-session status', function () {
    // CSRF token mismatches map to HttpException(419) in the exception handler
    // (TokenMismatchException → 419); abort(419) exercises the same view path.
    // (CSRF verification itself is bypassed under `APP_ENV=testing`.)
    $this->get('/__err/419')
        ->assertStatus(419)
        ->assertSee('Your session token expired.')
        ->assertSee('Go Back and Retry');
});

it('serves the custom 429 view and surfaces the Retry-After window', function () {
    $this->get('/__err/429')
        ->assertStatus(429)
        ->assertSee('Too many attempts — the vault needs a moment.')
        ->assertSee('Try again in', false)
        ->assertSee('About 45 seconds');
});

it('serves the custom 500 view without exposing the exception', function () {
    // Production path (APP_DEBUG=false): a bare exception becomes a 500 and
    // must render the same on-brand copy — never the exception message.
    config(['app.debug' => false]);

    Route::get('/__err/boom', fn () => throw new RuntimeException('TOP-SECRET-INTERNAL-DETAIL'));

    $this->get('/__err/boom')
        ->assertStatus(500)
        ->assertSee('Something jammed in the mechanism.')
        ->assertDontSee('TOP-SECRET-INTERNAL-DETAIL')
        ->assertDontSee('RuntimeException');
});

it('serves the custom 500 view for abort(500) as well', function () {
    $this->get('/__err/500')
        ->assertStatus(500)
        ->assertSee('Something jammed in the mechanism.');
});

it('serves the custom 503 view with an estimated return chip', function () {
    $this->get('/__err/503')
        ->assertStatus(503)
        ->assertSee('The vault is temporarily sealed for maintenance.')
        ->assertSee('Estimated return', false)
        ->assertSee('About 5 minutes');
});

it('serves the custom 503 view during real maintenance mode', function () {
    $this->artisan('down', ['--retry' => 300])->assertExitCode(0);

    try {
        $this->get('/')
            ->assertStatus(503)
            ->assertSee('The vault is temporarily sealed for maintenance.')
            ->assertSee('Estimated return', false)
            ->assertSee('About 5 minutes');
    } finally {
        $this->artisan('up');
    }
});

it('keeps the theme bootstrap on error pages so dark/light preference is honoured', function () {
    $html = $this->get('/__err/404')->getContent();

    expect($html)
        ->toContain("localStorage.getItem('zkpm_theme')")
        ->toContain("setAttribute('data-theme'")
        ->toContain('theme-pill');
});

it('renders error pages in RTL Arabic without breaking the shell', function () {
    app()->setLocale('ar');
    Lang::setLocale('ar');

    $this->get('/__err/404')
        ->assertStatus(404)
        ->assertSee('باب الخزنة ده مش موجود أصلاً.')
        ->assertSee('dir="rtl"', false);
});