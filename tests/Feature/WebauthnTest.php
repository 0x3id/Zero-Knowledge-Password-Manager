<?php

use App\Models\User;
use App\Models\WebauthnCredential;

test('authenticated users can fetch WebAuthn registration options', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('webauthn.register.options'))
        ->assertOk();

    expect($response->json('publicKey.challenge'))->toBeString()
        ->and($response->json('publicKey.rp.name'))->toBeString()
        ->and($response->json('publicKey.user.id'))->toBeString();
});

test('authenticated users can list their registered WebAuthn credentials', function () {
    $user = User::factory()->create();
    $cred = WebauthnCredential::create([
        'user_id' => $user->id,
        'credential_id' => 'test-cred-id-1',
        'public_key' => 'test-public-key-pem',
        'device_label' => 'YubiKey 5C',
        'counter' => 0,
    ]);

    $response = $this->actingAs($user)->getJson(route('webauthn.index'))
        ->assertOk();

    expect($response->json('credentials.0.device_label'))->toBe('YubiKey 5C');
});

test('users can remove their own registered credential', function () {
    $user = User::factory()->create();
    $cred = WebauthnCredential::create([
        'user_id' => $user->id,
        'credential_id' => 'test-cred-id-2',
        'public_key' => 'test-public-key-pem',
        'device_label' => 'MacBook TouchID',
        'counter' => 0,
    ]);

    $this->actingAs($user)->deleteJson(route('webauthn.destroy', $cred))
        ->assertOk();

    $this->assertDatabaseMissing('webauthn_credentials', ['id' => $cred->id]);
});

test('users cannot delete another users credential', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $cred = WebauthnCredential::create([
        'user_id' => $other->id,
        'credential_id' => 'other-cred-id',
        'public_key' => 'other-public-key-pem',
        'device_label' => 'Other Key',
        'counter' => 0,
    ]);

    $this->actingAs($user)->deleteJson(route('webauthn.destroy', $cred))
        ->assertStatus(404);

    $this->assertDatabaseHas('webauthn_credentials', ['id' => $cred->id]);
});
