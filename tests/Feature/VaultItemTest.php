<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use App\Models\VaultItem;

test('vault item listing returns ciphertext fields to the owner', function () {
    $user = User::factory()->create();
    $item = VaultItem::factory()->create([
        'user_id' => $user->id,
        'title' => 'GitHub',
        'username' => 'alice',
        'encrypted_password' => 'ciphertext-password',
        'encrypted_notes' => 'ciphertext-notes',
        'iv' => 'base64-iv',
    ]);

    $this->actingAs($user)->getJson(route('vault.items'))
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.id', $item->id)
        ->assertJsonPath('items.0.title', 'GitHub')
        ->assertJsonPath('items.0.encrypted_password', 'ciphertext-password')
        ->assertJsonPath('items.0.encrypted_notes', 'ciphertext-notes')
        ->assertJsonPath('items.0.iv', 'base64-iv');
});

test('vault item listing never exposes another users items', function () {
    $other = User::factory()->create();
    VaultItem::factory()->create(['user_id' => $other->id, 'title' => 'Secret']);

    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('vault.items'))
        ->assertOk()
        ->assertJsonCount(0, 'items');
});

test('users can create a vault item', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('vault.store'), [
        'title' => 'GitHub',
        'username' => 'alice',
        'encrypted_password' => 'ciphertext-password',
        'encrypted_notes' => 'ciphertext-notes',
        'iv' => 'base64-iv',
        'url' => 'https://github.com',
    ])->assertCreated()
        ->assertJsonPath('item.title', 'GitHub');

    $this->assertDatabaseHas('vault_items', [
        'user_id' => $user->id,
        'title' => 'GitHub',
        'encrypted_password' => 'ciphertext-password',
    ]);
});

test('vault item creation requires the encrypted payload fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('vault.store'), [
        'title' => 'GitHub',
    ])->assertStatus(422);

    $this->assertDatabaseCount('vault_items', 0);
});

test('users can update their own vault item', function () {
    $user = User::factory()->create();
    $item = VaultItem::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->putJson(route('vault.update', $item), [
        'title' => 'GitHub (work)',
        'username' => 'alice@corp.io',
        'encrypted_password' => 'fresh-ciphertext',
        'encrypted_notes' => null,
        'iv' => 'fresh-iv',
        'url' => null,
    ])->assertOk()
        ->assertJsonPath('item.title', 'GitHub (work)');

    $item->refresh();

    expect($item->encrypted_password)->toBe('fresh-ciphertext')
        ->and($item->iv)->toBe('fresh-iv');
});

test('users cannot update another users item', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $item = VaultItem::factory()->create(['user_id' => $owner->id, 'title' => 'Original']);

    $this->actingAs($attacker)->putJson(route('vault.update', $item), [
        'title' => 'Hijacked',
        'username' => 'x',
        'encrypted_password' => 'x',
        'iv' => 'x',
    ])->assertNotFound();

    expect($item->fresh()->title)->toBe('Original');
});

test('users can delete their own vault item', function () {
    $user = User::factory()->create();
    $item = VaultItem::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->deleteJson(route('vault.destroy', $item))
        ->assertOk()
        ->assertJsonPath('ok', true);

    $this->assertDatabaseMissing('vault_items', ['id' => $item->id]);
});

test('users cannot delete another users item', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $item = VaultItem::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($attacker)->deleteJson(route('vault.destroy', $item))->assertNotFound();

    $this->assertDatabaseHas('vault_items', ['id' => $item->id]);
});

test('vault mutations are recorded in the audit log', function () {
    $user = User::factory()->create();
    $item = VaultItem::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->postJson(route('vault.store'), [
        'title' => 'T', 'username' => 'u', 'encrypted_password' => 'p', 'iv' => 'i',
    ])->assertCreated();
    $this->actingAs($user)->putJson(route('vault.update', $item), [
        'title' => 'T2', 'username' => 'u', 'encrypted_password' => 'p', 'iv' => 'i',
    ])->assertOk();
    $this->actingAs($user)->deleteJson(route('vault.destroy', $item))->assertOk();

    $actions = AuditLog::where('user_id', $user->id)->orderBy('id')->pluck('action_type');

    expect($actions->toArray())->toBe(['vault_item_created', 'vault_item_updated', 'vault_item_deleted']);
});

test('vault endpoints require authentication', function () {
    $this->getJson(route('vault.items'))->assertStatus(401);
});

test('category_id must be a real UUID', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->postJson(route('vault.store'), [
        'title' => 'T', 'username' => 'u', 'encrypted_password' => 'p', 'iv' => 'i',
        'category_id' => $category->id,
    ])->assertCreated()->assertJsonPath('item.category_id', $category->id);

    $this->actingAs($user)->postJson(route('vault.store'), [
        'title' => 'T', 'username' => 'u', 'encrypted_password' => 'p', 'iv' => 'i',
        'category_id' => 1,
    ])->assertStatus(422);

    $this->assertDatabaseMissing('vault_items', ['category_id' => 1]);
});
