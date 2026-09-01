<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('valid factor 1 parks the user and redirects to the TOTP challenge', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'auth_hash_input' => 'zkpm-test-auth-hash-input',
    ]);

    $response->assertRedirect(route('totp.challenge', absolute: false));
    $this->assertGuest();
    expect(session()->get(AuthenticatedSessionController::PENDING_USER))->toBe($user->id);
});

test('users who never enrolled TOTP are logged in and forced to enrollment', function () {
    $user = User::factory()->totpIncomplete()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'auth_hash_input' => 'zkpm-test-auth-hash-input',
    ]);

    $response->assertRedirect(route('totp.setup', absolute: false));
    $this->assertAuthenticatedAs($user);
});

test('users cannot authenticate with an invalid auth hash input', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'auth_hash_input' => 'wrong-auth-hash-input',
    ]);

    $response->assertSessionHasErrors('auth_hash_input');
    $this->assertGuest();
});

test('unknown emails receive the same validation error', function () {
    $response = $this->post('/login', [
        'email' => 'ghost@nobody.io',
        'auth_hash_input' => 'zkpm-test-auth-hash-input',
    ]);

    $response->assertSessionHasErrors('auth_hash_input');
    $this->assertGuest();
});

test('remember me preference is carried into the TOTP challenge', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'auth_hash_input' => 'zkpm-test-auth-hash-input',
        'remember' => 1,
    ]);

    expect(session()->get(AuthenticatedSessionController::PENDING_REMEMBER))->toBeTrue();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/login');
});
