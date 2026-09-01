<?php

namespace App\Providers;

use App\Session\UuidSessionManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // All `id` columns across the schema are UUIDs, including the
        // sessions table: swap the framework session manager so every new
        // session receives a UUID v4 identifier.
        $this->app->singleton('session', function (mixed $app) {
            return new UuidSessionManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * Defines the named rate limiters used by the authentication routes
     * (the architecture mandates strict limits on login, salt lookup,
     * recovery, and TOTP endpoints).
     */
    public function boot(): void
    {
        // Enforce HTTPS for all generated URLs in production so signed
        // links, redirects, and asset/route generation never emit plain
        // http:// URLs even if a request happens to arrive over HTTP.
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Vault Console line-icon set: `<x-icon-NAME>` resolves to the
        // anonymous component files in resources/views/components/icons/.
        foreach (glob(resource_path('views/components/icons/*.blade.php')) ?: [] as $iconFile) {
            $name = basename($iconFile, '.blade.php');
            Blade::component('components.icons.'.$name, 'icon-'.$name);
        }

        // Per-IP login attempts: 5 per minute to blunt online guessing.
        RateLimiter::for('auth.login', fn (): Limit => Limit::perMinute(5));

        // Registration: 3 per minute per IP to slow mass signups.
        RateLimiter::for('auth.register', fn (): Limit => Limit::perMinute(3));

        // kdf_salt lookups: 10 per minute per IP (enumeration protection is
        // handled separately via decoy salts, the limiter bounds cost).
        RateLimiter::for('auth.salt', fn (): Limit => Limit::perMinute(10));

        // TOTP code attempts: 5 per minute per IP.
        RateLimiter::for('auth.totp', fn (): Limit => Limit::perMinute(5));

        // Recovery flows: 5 per minute per IP, both for OTP requests and
        // for the gate that releases encrypted_recovery_blob.
        RateLimiter::for('auth.recovery', fn (): Limit => Limit::perMinute(5));

        // Resend verification email: 1 per 60 seconds per user.
        RateLimiter::for('auth.verification', fn (Request $request): Limit => Limit::perMinute(1)->by($request->user()?->id));
    }
}
