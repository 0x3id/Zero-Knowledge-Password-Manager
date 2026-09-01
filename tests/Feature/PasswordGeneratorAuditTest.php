<?php

use App\Models\AuditLog;
use App\Models\User;

test('the generator audit endpoint requires authentication', function () {
    $this->postJson(route('generator.audit'))->assertUnauthorized();
});

test('generating a password records an audit entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('generator.audit'))->assertOk();

    expect(AuditLog::where('user_id', $user->id)
        ->where('action_type', 'password_generated')
        ->count())->toBe(1);
});