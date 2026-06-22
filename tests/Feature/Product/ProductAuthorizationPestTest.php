<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to access product creation', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('products.create'))
        ->assertOk();
});

it('allows an admin to create products', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('products.store'), [
            'sku' => 'PRD-000801',
            'title' => 'Admin Created Product',
            'description' => '<p>Administrators can create products.</p>',
            'price' => '10.00',
            'quantity' => '5',
            'date_available' => '2026-07-01',
        ])
        ->assertSessionHasNoErrors();

    expect(Product::where('sku', 'PRD-000801')->exists())->toBeTrue();
});

it('allows an admin to edit products', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->get(route('products.edit', $product))
        ->assertOk();
});

it('allows an admin to delete products', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    expect(Product::query()->whereKey($product)->exists())->toBeFalse();
});

it('allows a standard user to view products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['title' => 'Visible Product']);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('Visible Product');

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Visible Product');
});

it('forbids a standard user from creating products', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('products.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('products.store'), [
            'title' => 'Forbidden Product',
            'description' => '<p>Standard users cannot create products.</p>',
            'price' => '10.00',
            'quantity' => '5',
            'date_available' => '2026-07-01',
        ])
        ->assertForbidden();
});

it('forbids a standard user from deleting products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertForbidden();

    expect(Product::query()->whereKey($product)->exists())->toBeTrue();
});

it('forbids a standard user from updating products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->get(route('products.edit', $product))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('products.update', $product), [
            'title' => 'Forbidden Update',
            'description' => '<p>Standard users cannot update products.</p>',
            'price' => '10.00',
            'quantity' => '5',
            'date_available' => '2026-07-01',
        ])
        ->assertForbidden();
});

it('redirects guests away from product routes', function (string $method, string $uri) {
    $this->{$method}($uri)->assertRedirect(route('login'));
})->with([
    'index' => ['get', '/products'],
    'create' => ['get', '/products/create'],
    'store' => ['post', '/products'],
]);
