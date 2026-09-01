<?php

use App\Jobs\SendVerificationEmail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

test('unverified users are sent to the verification notice before the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('unverified users are sent to the verification notice before the vault', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('vault'))
        ->assertRedirect(route('verification.notice'));
});

test('verified users with TOTP complete can open the vault', function () {
    App::setLocale('en');
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('vault'))
        ->assertStatus(200)
        ->assertSee('Decrypted Vault');
});

test('the verification notice page renders for unverified users', function () {
    App::setLocale('en');
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertStatus(200)
        ->assertSee('Verify Your Email');
});

test('already verified users are redirected away from the verification notice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertRedirect(route('dashboard'));
});

test('the resend endpoint dispatches a fresh queued verification job', function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    Queue::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');

    Queue::assertPushed(SendVerificationEmail::class);
});

test('the resend endpoint is a no-op for already verified users', function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('verification.send'))
        ->assertRedirect(route('dashboard'));

    Queue::assertNothingPushed();
});

test('a valid verification token confirms the email and redirects to TOTP setup', function () {
    $user = User::factory()->unverified()->create();

    $tokenHash = hash('sha256', Str::random(64));

    DB::table('email_verification_tokens')->insert([
        'id' => Str::uuid(),
        'user_id' => $user->id,
        'token_hash' => $tokenHash,
        'expires_at' => now()->addMinutes(60),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => $tokenHash],
    );

    $this->actingAs($user)->get($url)->assertRedirect(route('totp.setup'));

    $user->refresh();

    expect($user->hasVerifiedEmail())->toBeTrue();

    expect(DB::table('email_verification_tokens')
        ->where('token_hash', $tokenHash)
        ->value('used_at'))->not->toBeNull();
});

test('an invalid verification hash redirects to the verification notice', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => hash('sha256', 'wrong-token')],
    );

    $this->actingAs($user)->get($url)->assertRedirect(route('verification.notice'));

    $user->refresh();

    expect($user->hasVerifiedEmail())->toBeFalse();
});

test('an already-used verification link shows the already verified page', function () {
    $user = User::factory()->unverified()->create();

    $tokenHash = hash('sha256', Str::random(64));

    DB::table('email_verification_tokens')->insert([
        'id' => Str::uuid(),
        'user_id' => $user->id,
        'token_hash' => $tokenHash,
        'expires_at' => now()->addMinutes(60),
        'used_at' => now()->subMinute(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => $tokenHash],
    );

    $this->actingAs($user)->get($url)
        ->assertOk()
        ->assertSee('already been verified');
});

test('an expired verification link redirects to the verification notice for resend', function () {
    $user = User::factory()->unverified()->create();

    $tokenHash = hash('sha256', Str::random(64));

    DB::table('email_verification_tokens')->insert([
        'id' => Str::uuid(),
        'user_id' => $user->id,
        'token_hash' => $tokenHash,
        'expires_at' => now()->subMinute(),
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => $tokenHash],
    );

    $this->actingAs($user)->get($url)->assertRedirect(route('verification.notice'));

    $user->refresh();

    expect($user->hasVerifiedEmail())->toBeFalse();
});