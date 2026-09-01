<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk();
});

test('profile email can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'username' => 'newname',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->email)->toBe('test@example.com')
        ->and($user->username)->toBe('newname');
});

test('profile email must be unique', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', ['username' => $user->username, 'email' => 'taken@example.com'])
        ->assertSessionHasErrors('email');
});

test('user can delete their account with the email confirmation', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', ['email' => $user->email]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('a mismatched email prevents account deletion', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', ['email' => 'wrong@example.com']);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'email')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('the guest layout renders the recovery and login links', function () {
    $this->get('/login')->assertOk();
    $this->get('/recovery')->assertOk();
});
