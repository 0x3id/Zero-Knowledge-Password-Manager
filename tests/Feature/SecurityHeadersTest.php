<?php

use App\Models\User;

test('security headers including strict CSP and X-Frame-Options are returned on web responses', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertHeader('Content-Security-Policy');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    $csp = (string) $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain('https://api.pwnedpasswords.com')
        ->and($csp)->toContain("frame-ancestors 'none'");
});
