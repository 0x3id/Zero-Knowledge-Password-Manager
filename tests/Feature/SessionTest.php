<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('users can list their active sessions', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'session-123',
        'user_id' => $user->id,
        'ip_address' => '192.168.1.10',
        'device_name' => 'Chrome on macOS',
        'is_remember_me' => false,
        'last_active_at' => now(),
        'expires_at' => now()->addDay(),
        'last_activity' => time(),
        'payload' => 'dummy',
    ]);

    $response = $this->actingAs($user)->get(route('sessions.index'))
        ->assertOk();

    $response->assertSee('Chrome on macOS');
    $response->assertSee('192.168.1.10');
});

test('users can revoke a specific session other than the current session', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'other-session-id',
        'user_id' => $user->id,
        'ip_address' => '10.0.0.5',
        'device_name' => 'Firefox on Windows',
        'is_remember_me' => false,
        'last_active_at' => now(),
        'expires_at' => now()->addDay(),
        'last_activity' => time(),
        'payload' => 'dummy',
    ]);

    $this->actingAs($user)->deleteJson(route('sessions.destroy', 'other-session-id'))
        ->assertOk();

    $this->assertDatabaseMissing('sessions', ['id' => 'other-session-id']);
});

test('users cannot revoke their current session via the destroy endpoint', function () {
    $user = User::factory()->create();

    $controller = new \App\Http\Controllers\SessionController();
    $request = Request::create('/sessions/curr-sess', 'DELETE', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $session = app('session.store');
    $session->start();
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $jsonRes = $controller->destroy($request, $session->getId(), app(\App\Services\AuditLogger::class));
    expect($jsonRes->getStatusCode())->toBe(422);
    expect($jsonRes->getData()->message)->toContain('Cannot revoke current session');
});

test('users can revoke all other sessions', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        [
            'id' => 'sess-1',
            'user_id' => $user->id,
            'ip_address' => '1.1.1.1',
            'device_name' => 'Device 1',
            'is_remember_me' => false,
            'last_active_at' => now(),
            'expires_at' => now()->addDay(),
            'last_activity' => time(),
            'payload' => 'p1',
        ],
        [
            'id' => 'sess-2',
            'user_id' => $user->id,
            'ip_address' => '2.2.2.2',
            'device_name' => 'Device 2',
            'is_remember_me' => false,
            'last_active_at' => now(),
            'expires_at' => now()->addDay(),
            'last_activity' => time(),
            'payload' => 'p2',
        ],
    ]);

    $this->actingAs($user)->postJson(route('sessions.destroy-others'))
        ->assertOk();

    $this->assertDatabaseMissing('sessions', ['id' => 'sess-1']);
    $this->assertDatabaseMissing('sessions', ['id' => 'sess-2']);
});

test('login stamps device metadata and a 24-hour expiry on the session row', function () {
    $user = User::factory()->totpIncomplete()->create();

    $this->post('/login', [
        'email' => $user->email,
        'auth_hash_input' => 'zkpm-test-auth-hash-input',
    ])->assertRedirect(route('totp.setup', absolute: false));

    $row = DB::table('sessions')->where('user_id', $user->id)->latest('last_active_at')->first();

    expect($row)->not->toBeNull()
        ->and($row->device_name)->not->toBeNull()
        ->and((bool) $row->is_remember_me)->toBeFalse()
        ->and(Str::isUuid($row->id))->toBeTrue()
        ->and(Carbon::parse($row->expires_at)->isAfter(now()->addHours(23)))->toBeTrue()
        ->and(Carbon::parse($row->expires_at)->isBefore(now()->addHours(25)))->toBeTrue();
});

test('remember me sessions get a 7-day lifetime', function () {
    $user = User::factory()->totpIncomplete()->create();

    $this->post('/login', [
        'email' => $user->email,
        'auth_hash_input' => 'zkpm-test-auth-hash-input',
        'remember' => true,
    ])->assertRedirect(route('totp.setup', absolute: false));

    $row = DB::table('sessions')->where('user_id', $user->id)->latest('last_active_at')->first();

    expect($row)->not->toBeNull()
        ->and((bool) $row->is_remember_me)->toBeTrue()
        ->and(Carbon::parse($row->expires_at)->isAfter(now()->addDays(6)))->toBeTrue()
        ->and(Carbon::parse($row->expires_at)->isBefore(now()->addDays(8)))->toBeTrue();
});

test('expired sessions are invalidated server-side and removed', function () {
    $user = User::factory()->create();
    $sid = Str::uuid()->toString();

    DB::table('sessions')->insert([
        'id' => $sid,
        'user_id' => $user->id,
        'device_name' => 'Chrome on Linux',
        'is_remember_me' => false,
        'last_active_at' => now()->subHour(),
        'expires_at' => now()->subMinute(),
        'last_activity' => time() - 3600,
        'payload' => base64_encode(serialize(['_token' => 'legacy-token'])),
    ]);

    $request = Request::create('/dashboard');
    $session = app('session.store');
    $session->setId($sid);
    $session->start();
    $request->setLaravelSession($session);

    $response = (new App\Http\Middleware\SessionLifespan())->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toEndWith('/login')
        ->and(session('status'))->not->toBeNull();

    $this->assertDatabaseMissing('sessions', ['id' => $sid]);
});

test('live sessions pass through the lifespan middleware untouched', function () {
    $user = User::factory()->create();
    $sid = Str::uuid()->toString();

    DB::table('sessions')->insert([
        'id' => $sid,
        'user_id' => $user->id,
        'device_name' => 'Chrome on Linux',
        'is_remember_me' => false,
        'last_active_at' => now(),
        'expires_at' => now()->addDay(),
        'last_activity' => time(),
        'payload' => base64_encode(serialize(['_token' => 'legacy-token'])),
    ]);

    $request = Request::create('/dashboard');
    $session = app('session.store');
    $session->setId($sid);
    $session->start();
    $request->setLaravelSession($session);

    $response = (new App\Http\Middleware\SessionLifespan())->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('ok');

    $this->assertDatabaseHas('sessions', ['id' => $sid]);
});
