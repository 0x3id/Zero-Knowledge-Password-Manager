<?php

use App\Http\Controllers\Auth\RecoveryController;
use App\Mail\RecoveryCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('the recovery screen can be rendered', function () {
    $this->get(route('recovery'))->assertStatus(200);
});

test('recovery OTP requests return a generic response for unknown emails', function () {
    Mail::fake();

    $response = $this->postJson(route('recovery.request-otp'), [
        'email' => 'ghost@nobody.io',
    ]);

    $response->assertOk();
    Mail::assertNothingSent();
});

test('recovery OTP requests send a code for registered emails', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->postJson(route('recovery.request-otp'), ['email' => $user->email])->assertOk();

    Mail::assertQueued(RecoveryCodeMail::class, function (RecoveryCodeMail $mail) use ($user) {
        return preg_match('/^\d{6}$/', $mail->code) === 1
            && $mail->hasTo($user->email);
    });
});

test('recovery verify rejects an invalid OTP', function () {
    $user = User::factory()->create();

    $this->withSession([RecoveryController::OTP_CACHE_PREFIX.$user->email => [
        'otp' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10)->getTimestamp(),
    ]])->postJson(route('recovery.verify'), [
        'email' => $user->email,
        'otp' => '654321',
    ])->assertStatus(422);
});

test('recovery verify returns the recovery payload and wipes 2FA for a valid OTP', function () {
    $user = User::factory()->create();
    $pendingKey = RecoveryController::OTP_CACHE_PREFIX.$user->email;

    $response = $this->withSession([
        $pendingKey => [
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
        ],
    ])->postJson(route('recovery.verify'), [
        'email' => $user->email,
        'otp' => '123456',
    ])->assertOk();

    $response->assertJsonPath('encrypted_recovery_blob', $user->encrypted_recovery_blob)
        ->assertJsonPath('vault_canary', $user->vault_canary);

    $user->refresh();

    expect($user->totp_secret)->toBeNull()
        ->and($user->is_2fa_enabled)->toBeFalse()
        ->and($user->is_totp_complete)->toBeFalse()
        ->and(session()->get(RecoveryController::PENDING_RECOVERY))->toBe($user->id);
});

test('recovery OTPs are single use', function () {
    $user = User::factory()->create();
    $pendingKey = RecoveryController::OTP_CACHE_PREFIX.$user->email;

    $this->withSession([
        $pendingKey => [
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
        ],
    ])->postJson(route('recovery.verify'), [
        'email' => $user->email,
        'otp' => '123456',
    ])->assertOk();

    // The OTP was pulled from the session by the first request: a second
    // attempt (same session, not re-seeded) must fail.
    $this->postJson(route('recovery.verify'), [
        'email' => $user->email,
        'otp' => '123456',
    ])->assertStatus(422);
});

test('password reset updates the auth hash and redirects to TOTP re-enrollment', function () {
    $user = User::factory()->create();

    $this->withSession([RecoveryController::PENDING_RECOVERY => $user->id])
        ->postJson(route('recovery.reset-password'), [
            'new_auth_hash_input' => 'fresh-derived-auth-hash-input',
            'kdf_salt' => 'ZGVjb2RlZC1uZXctc2FsdA==',
            'kdf_params' => json_encode(['algorithm' => 'PBKDF2-SHA256', 'iterations' => 600000]),
        ])->assertRedirect(route('totp.setup'));

    $user->refresh();

    expect(Hash::check('fresh-derived-auth-hash-input', $user->auth_hash))->toBeTrue()
        ->and($user->kdf_salt)->toBe('ZGVjb2RlZC1uZXctc2FsdA==')
        ->and(session()->has(RecoveryController::PENDING_RECOVERY))->toBeFalse()
        ->and(auth()->id())->toBe($user->id);
});

test('password reset requires an authorized recovery session', function () {
    $this->postJson(route('recovery.reset-password'), [
        'new_auth_hash_input' => 'fresh-derived-auth-hash-input',
        'kdf_salt' => 'ZGVjb2RlZC1uZXctc2FsdA==',
        'kdf_params' => json_encode(['algorithm' => 'PBKDF2-SHA256', 'iterations' => 600000]),
    ])->assertStatus(403);
});
