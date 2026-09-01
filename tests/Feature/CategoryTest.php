<?php

use App\Models\Category;
use App\Models\User;
use App\Models\VaultItem;

test('users can list their own categories', function () {
    $user = User::factory()->create();
    $cat1 = Category::factory()->create(['user_id' => $user->id, 'name' => 'Personal']);
    $cat2 = Category::factory()->create(['user_id' => $user->id, 'name' => 'Work']);

    // Another user's category
    $otherUser = User::factory()->create();
    Category::factory()->create(['user_id' => $otherUser->id, 'name' => 'Secret']);

    $response = $this->actingAs($user)->getJson(route('categories.index'))
        ->assertOk();

    $categoryNames = collect($response->json('categories'))->pluck('name');
    expect($categoryNames)->toContain('Personal', 'Work')
        ->and($categoryNames)->not->toContain('Secret');
});

test('users can create a new category', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('categories.store'), [
        'name' => 'Finance',
    ])->assertStatus(201);

    expect($response->json('category.name'))->toBe('Finance');

    $this->assertDatabaseHas('categories', [
        'user_id' => $user->id,
        'name' => 'Finance',
    ]);
});

test('users can update their own category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

    $response = $this->actingAs($user)->putJson(route('categories.update', $category), [
        'name' => 'New Name',
    ])->assertOk();

    expect($response->json('category.name'))->toBe('New Name');
    expect($category->fresh()->name)->toBe('New Name');
});

test('users cannot update another user category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $otherUser->id, 'name' => 'Private']);

    $this->actingAs($user)->putJson(route('categories.update', $category), [
        'name' => 'Hacked',
    ])->assertStatus(404);

    expect($category->fresh()->name)->toBe('Private');
});

test('users can delete their own category and nullify associated vault items', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'To Delete']);
    $item = VaultItem::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Sample',
        'username' => 'user@example.com',
        'encrypted_password' => 'cipher',
        'encrypted_notes' => null,
        'iv' => 'iv123',
    ]);

    $this->actingAs($user)->deleteJson(route('categories.destroy', $category))
        ->assertOk();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    expect($item->fresh()->category_id)->toBeNull();
});

test('users cannot delete another user category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Category']);

    $this->actingAs($user)->deleteJson(route('categories.destroy', $category))
        ->assertStatus(404);

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
