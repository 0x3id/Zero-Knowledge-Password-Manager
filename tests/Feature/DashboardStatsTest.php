<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

function totpCompleteUser(): User
{
    return User::factory()->create();
}

test('the dashboard shows core counters for authenticated users', function () {
    App::setLocale('en');

    $user = totpCompleteUser();
    VaultItem::factory()->count(3)->create(['user_id' => $user->id]);
    Category::factory()->create(['user_id' => $user->id]);

    AuditLog::create([
        'user_id' => $user->id,
        'action_type' => 'password_generated',
        'created_at' => now(),
    ]);
    AuditLog::create([
        'user_id' => $user->id,
        'action_type' => 'password_generated',
        'created_at' => now()->subDays(10),
    ]);

    DB::table('sessions')->insert([
        'id' => str()->random(40),
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'payload' => '{}',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Vault Items')
        ->assertSee('Passwords Generated')
        ->assertSee('Generated This Week')
        ->assertSee('Audit Events')
        ->assertSee('Active Sessions')
        ->assertSee('Member Since')
        ->assertSee('3')
        ->assertSee('1')
        ->assertSee('2')
        ->assertSee('Weekly Activity')
        ->assertSee('Open Vault')
        ->assertSee('Security Checkup');
});

test('the dashboard renders a weekly chart with localized day labels', function () {
    App::setLocale('en');

    $user = totpCompleteUser();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Mon')
        ->assertSee('Sun');
});

test('the dashboard lists recent activity with localized labels', function () {
    App::setLocale('en');

    $user = totpCompleteUser();
    AuditLog::create(['user_id' => $user->id, 'action_type' => 'login', 'created_at' => now()]);
    AuditLog::create(['user_id' => $user->id, 'action_type' => 'password_generated', 'created_at' => now()->subMinute()]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('User Login')
        ->assertSee('Password Generated')
        ->assertSee('Recent Activity');
});

test('the dashboard shows an empty state when there is no activity', function () {
    App::setLocale('en');

    $user = totpCompleteUser();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No activity yet');
});

test('users who have not completed TOTP are sent to enrollment before the dashboard', function () {
    $user = User::factory()->totpIncomplete()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('totp.setup'));
});