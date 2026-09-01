<?php

use App\Models\AuditLog;
use App\Models\User;

test('users can view their own activity audit log', function () {
    App::setLocale('en');
    $user = User::factory()->create();

    AuditLog::create([
        'user_id' => $user->id,
        'action_type' => 'login',
        'ip_address' => '127.0.0.1',
        'device_info' => 'Safari on iOS',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('audit-logs.index'))
        ->assertOk();

    $response->assertSee('User Login');
    $response->assertSee('Safari on iOS');
    $response->assertSee('127.0.0.1');
});

test('users cannot view other users audit logs', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    AuditLog::create([
        'user_id' => $other->id,
        'action_type' => 'vault_item_created',
        'ip_address' => '10.10.10.10',
        'device_info' => 'Edge on Windows',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->getJson(route('audit-logs.index'))
        ->assertOk();

    $ipAddresses = collect($response->json('data'))->pluck('ip_address');
    expect($ipAddresses)->not->toContain('10.10.10.10');
});

test('audit logs can be filtered by action type', function () {
    App::setLocale('en');
    $user = User::factory()->create();

    AuditLog::create([
        'user_id' => $user->id,
        'action_type' => 'login',
        'ip_address' => '1.1.1.1',
        'device_info' => 'Chrome',
        'created_at' => now(),
    ]);

    AuditLog::create([
        'user_id' => $user->id,
        'action_type' => 'vault_item_deleted',
        'ip_address' => '2.2.2.2',
        'device_info' => 'Firefox',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('audit-logs.index', ['action_type' => 'vault_item_deleted']))
        ->assertOk();

    $response->assertSee('Item Deleted');
    $response->assertSee('2.2.2.2');
});
