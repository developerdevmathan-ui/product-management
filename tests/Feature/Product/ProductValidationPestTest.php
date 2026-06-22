<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a product sku when sku is missing', function () {
    $admin = User::factory()->admin()->create();

    Product::factory()->create(['sku' => 'PRD-000010']);

    $this->actingAs($admin)
        ->from(route('products.create'))
        ->post(route('products.store'), [
            'title' => 'Generated SKU Product',
            'description' => '<p>Useful product description.</p>',
            'price' => '10.00',
            'quantity' => '5',
            'date_available' => '2026-07-01',
        ])
        ->assertSessionHasNoErrors();

    expect(Product::where('title', 'Generated SKU Product')->firstOrFail())
        ->sku->toBe('PRD-000011');
});

it('rejects invalid product input', function (array $payload, string $field) {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->from(route('products.create'))
        ->post(route('products.store'), array_merge([
            'title' => 'Valid Product',
            'description' => '<p>Useful product description.</p>',
            'price' => '10.00',
            'quantity' => '5',
            'date_available' => '2026-07-01',
        ], $payload));

    $response
        ->assertRedirect(route('products.create'))
        ->assertSessionHasErrors($field);

    expect(Product::count())->toBe(0);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'missing description' => [['description' => ''], 'description'],
    'empty rich text description' => [['description' => '<p><br></p>'], 'description'],
    'invalid sku format' => [['sku' => 'BAD-1'], 'sku'],
    'invalid price' => [['price' => 'free'], 'price'],
    'zero price' => [['price' => '0'], 'price'],
    'missing quantity' => [['quantity' => ''], 'quantity'],
    'negative quantity' => [['quantity' => '-1'], 'quantity'],
    'invalid date' => [['date_available' => '07/01/2026'], 'date_available'],
]);

it('rejects duplicate product sku input', function () {
    $admin = User::factory()->admin()->create();

    Product::factory()->create(['sku' => 'PRD-000123']);

    $this->actingAs($admin)
        ->from(route('products.create'))
        ->post(route('products.store'), [
            'sku' => 'PRD-000123',
            'title' => 'Duplicate SKU Product',
            'description' => '<p>Useful product description.</p>',
            'price' => '10.00',
            'quantity' => '5',
            'date_available' => '2026-07-01',
        ])
        ->assertRedirect(route('products.create'))
        ->assertSessionHasErrors('sku');
});

it('allows an unchanged sku during product update', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['sku' => 'PRD-000124']);

    $this->actingAs($admin)
        ->from(route('products.edit', $product))
        ->put(route('products.update', $product), [
            'sku' => 'PRD-000124',
            'title' => 'Updated Unique SKU Product',
            'description' => '<p>Useful product description.</p>',
            'price' => '10.00',
            'quantity' => '5',
            'date_available' => '2026-07-01',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.show', $product));

    expect($product->refresh())
        ->sku->toBe('PRD-000124')
        ->title->toBe('Updated Unique SKU Product');
});

it('rejects invalid product update input', function (array $payload, string $field) {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)
        ->from(route('products.edit', $product))
        ->put(route('products.update', $product), array_merge([
            'title' => 'Valid Product',
            'description' => '<p>Useful product description.</p>',
            'price' => '10.00',
            'quantity' => '5',
            'date_available' => '2026-07-01',
        ], $payload));

    $response
        ->assertRedirect(route('products.edit', $product))
        ->assertSessionHasErrors($field);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'missing description' => [['description' => ''], 'description'],
    'invalid price' => [['price' => '-1'], 'price'],
    'negative quantity' => [['quantity' => '-1'], 'quantity'],
    'invalid quantity' => [['quantity' => '1.5'], 'quantity'],
    'invalid date' => [['date_available' => 'tomorrow'], 'date_available'],
]);
