<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\TOTPController;
use App\Models\User;
use App\Services\TOTPService;

test('the TOTP challenge requires a pending login', function () {
    $response = $this->get(route('totp.challenge'));

    $response->assertRedirect(route('login'));
});

test('the TOTP challenge renders for a pending login', function () {
    $user = User::factory()->create();

    $this->withSession([
        AuthenticatedSessionController::PENDING_USER => $user->id,
        AuthenticatedSessionController::PENDING_REMEMBER => false,
    ])->get(route('totp.challenge'))->assertStatus(200);
});

test('TOTP verification completes authentication with a valid code', function () {
    $user = User::factory()->create();
    $code = app(TOTPService::class)->codeAt($user->totp_secret);

    $this->withSession([
        AuthenticatedSessionController::PENDING_USER => $user->id,
        AuthenticatedSessionController::PENDING_REMEMBER => false,
    ])->postJson(route('totp.verify'), ['code' => $code])
        ->assertOk()
        ->assertJsonPath('redirect', route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('TOTP verification rejects an invalid code', function () {
    $user = User::factory()->create();

    $this->withSession([
        AuthenticatedSessionController::PENDING_USER => $user->id,
        AuthenticatedSessionController::PENDING_REMEMBER => false,
    ])->postJson(route('totp.verify'), ['code' => '000000'])
        ->assertStatus(422);

    $this->assertGuest();
});

test('TOTP verification rejects a malformed code', function () {
    $user = User::factory()->create();

    $this->withSession([
        AuthenticatedSessionController::PENDING_USER => $user->id,
        AuthenticatedSessionController::PENDING_REMEMBER => false,
    ])->postJson(route('totp.verify'), ['code' => 'abc'])
        ->assertStatus(422);

    $this->assertGuest();
});

test('TOTP verification fails without a pending login', function () {
    $this->postJson(route('totp.verify'), ['code' => '123456'])
        ->assertStatus(403);
});

test('the enrollment page redirects users who already completed TOTP', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('totp.setup'))
        ->assertRedirect(route('dashboard'));
});

test('the enrollment page renders for incomplete users', function () {
    $user = User::factory()->totpIncomplete()->create();

    $this->actingAs($user)->get(route('totp.setup'))->assertStatus(200);
});

test('pending secret generation returns a valid provisioning URI', function () {
    $user = User::factory()->totpIncomplete()->create();

    $response = $this->actingAs($user)->postJson(route('totp.setup.options'))
        ->assertOk();

    expect($response->json('secret'))->toMatch('/^[A-Z2-7]+$/')
        ->and($response->json('uri'))->toContain('otpauth://totp/')
        ->and(session()->get(TOTPController::PENDING_SECRET))->toBe($response->json('secret'));
});

test('enrollment is completed with a valid code', function () {
    $user = User::factory()->totpIncomplete()->create();

    $response = $this->actingAs($user)->postJson(route('totp.setup.options'))->assertOk();
    $secret = $response->json('secret');
    $code = app(TOTPService::class)->codeAt($secret);

    $this->actingAs($user)->postJson(route('totp.setup.verify'), ['code' => $code])
        ->assertOk()
        ->assertJsonPath('redirect', route('dashboard'));

    $user->refresh();

    expect($user->is_totp_complete)->toBeTrue()
        ->and($user->is_2fa_enabled)->toBeTrue()
        ->and($user->totp_secret)->toBe($secret);
});

test('enrollment rejects an invalid code and clears the pending secret', function () {
    $user = User::factory()->totpIncomplete()->create();

    $this->actingAs($user)->postJson(route('totp.setup.options'))->assertOk();

    $this->actingAs($user)->postJson(route('totp.setup.verify'), ['code' => '000000'])
        ->assertStatus(422);

    $user->refresh();

    expect($user->is_totp_complete)->toBeFalse()
        ->and(session()->has(TOTPController::PENDING_SECRET))->toBeFalse();
});
