<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists products for a standard user', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Roadmap Planner']);
    Product::factory()->create(['title' => 'Release Tracker']);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('Roadmap Planner')
        ->assertSee('Release Tracker');
});

it('shows product details for a standard user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'title' => 'Discovery Workspace',
        'description' => '<p>Research and planning workspace.</p>',
        'price' => '49.99',
        'date_available' => '2026-07-15',
    ]);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Discovery Workspace')
        ->assertSee('49.99')
        ->assertSee('Research and planning workspace.');
});

it('creates a product as an admin', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->from(route('products.create'))
        ->post(route('products.store'), [
            'title' => 'Secure Product',
            'description' => '<p>Safe copy</p><script>alert(1)</script><a href="javascript:alert(1)">bad link</a>',
            'price' => '19.99',
            'date_available' => '2026-07-01',
            'role' => 'admin',
        ]);

    $product = Product::where('title', 'Secure Product')->firstOrFail();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.show', $product));

    expect($product->description)
        ->toContain('<p>Safe copy</p>')
        ->not->toContain('<script>')
        ->not->toContain('javascript:')
        ->and($product->price)->toBe('19.99')
        ->and($product->date_available->toDateString())->toBe('2026-07-01');
});

it('updates a product as an admin', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->from(route('products.edit', $product))
        ->put(route('products.update', $product), [
            'title' => 'Updated Product',
            'description' => '<h2>Updated</h2><img src=x onerror=alert(1)>',
            'price' => '29.95',
            'date_available' => '2026-08-15',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.show', $product));

    expect($product->refresh())
        ->title->toBe('Updated Product')
        ->description->toContain('<h2>Updated</h2>')
        ->description->not->toContain('<img')
        ->price->toBe('29.95')
        ->date_available->toDateString()->toBe('2026-08-15');
});

it('deletes a product as an admin', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    expect(Product::query()->whereKey($product)->exists())->toBeFalse();
});
