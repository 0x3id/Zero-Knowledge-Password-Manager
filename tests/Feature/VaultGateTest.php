<?php

use App\Models\User;
use Illuminate\Support\Facades\App;

test('the landing page renders for guest visitors', function () {
    $this->get('/')->assertOk()->assertSee('ZeroKnowledge');
});

test('guest visitors are redirected to the login screen when opening the dashboard', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('authenticated users are sent from the landing page to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
});

test('the dashboard renders for authenticated users who completed TOTP', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertStatus(200);
});

test('users who have not completed TOTP are forced to enrollment', function () {
    $user = User::factory()->totpIncomplete()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('totp.setup'));
});

test('guest visitors are redirected to the login screen when opening the vault page', function () {
    $this->get('/vault')->assertRedirect(route('login'));
});

test('the vault page renders for authenticated users who completed TOTP', function () {
    App::setLocale('en');

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('vault'))->assertStatus(200)->assertSee('Decrypted Vault');
});

test('users who have not completed TOTP are forced to enrollment before the vault page', function () {
    $user = User::factory()->totpIncomplete()->create();

    $this->actingAs($user)->get(route('vault'))->assertRedirect(route('totp.setup'));
});

test('the lock data endpoint exposes the vault canary', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('lock-data'))
        ->assertOk()
        ->assertJsonPath('vault_canary', $user->vault_canary)
        ->assertJsonPath('has_webauthn', false);
});

test('the lock data endpoint requires authentication', function () {
    $this->get(route('lock-data'))->assertRedirect(route('login'));
});
