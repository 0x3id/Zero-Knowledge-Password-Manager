<?php

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register with the zero-knowledge payload', function () {
    Mail::fake();

    $response = $this->post('/register', [
        'username' => 'alice',
        'email' => 'alice@example.com',
        'auth_hash_input' => 'client-derived-auth-hash-input',
        'kdf_salt' => 'MDEyMzQ1Njc4OWFiY2RlZg==',
        'kdf_params' => json_encode(['algorithm' => 'PBKDF2-SHA256', 'iterations' => 600000]),
        'encrypted_recovery_blob' => 'encrypted-recovery-blob',
        'vault_canary' => 'vault-canary',
    ]);

    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::where('email', 'alice@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->auth_hash)->not->toBe('client-derived-auth-hash-input')
        ->and(Hash::check('client-derived-auth-hash-input', $user->auth_hash))->toBeTrue()
        ->and($user->is_totp_complete)->toBeFalse()
        ->and($user->kdf_salt)->toBe('MDEyMzQ1Njc4OWFiY2RlZg==')
        ->and($user->vault_canary)->toBe('vault-canary')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Mail::assertSent(VerifyEmail::class, function (VerifyEmail $mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

test('new users are authenticated and routed to mandatory email verification', function () {
    $this->post('/register', [
        'username' => 'alice',
        'email' => 'alice@example.com',
        'auth_hash_input' => 'client-derived-auth-hash-input',
        'kdf_salt' => 'MDEyMzQ1Njc4OWFiY2RlZg==',
        'kdf_params' => json_encode(['algorithm' => 'PBKDF2-SHA256', 'iterations' => 600000]),
        'encrypted_recovery_blob' => 'encrypted-recovery-blob',
        'vault_canary' => 'vault-canary',
    ]);

    $this->assertAuthenticated();
});

test('registration requires the zero-knowledge payload fields', function () {
    $response = $this->post('/register', [
        'email' => 'alice@example.com',
    ]);

    $response->assertSessionHasErrors(['username', 'auth_hash_input', 'kdf_salt', 'kdf_params', 'encrypted_recovery_blob', 'vault_canary']);
    $this->assertDatabaseCount('users', 0);
});

test('registration rejects duplicate emails', function () {
    User::factory()->create(['email' => 'alice@example.com']);

    $this->post('/register', [
        'username' => 'alice',
        'email' => 'alice@example.com',
        'auth_hash_input' => 'client-derived-auth-hash-input',
        'kdf_salt' => 'MDEyMzQ1Njc4OWFiY2RlZg==',
        'kdf_params' => json_encode(['algorithm' => 'PBKDF2-SHA256', 'iterations' => 600000]),
        'encrypted_recovery_blob' => 'encrypted-recovery-blob',
        'vault_canary' => 'vault-canary',
    ])->assertSessionHasErrors('email');
});
