<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects invalid product input', function (array $payload, string $field) {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->from(route('products.create'))
        ->post(route('products.store'), array_merge([
            'title' => 'Valid Product',
            'description' => '<p>Useful product description.</p>',
            'price' => '10.00',
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
    'invalid price' => [['price' => 'free'], 'price'],
    'zero price' => [['price' => '0'], 'price'],
    'invalid date' => [['date_available' => '07/01/2026'], 'date_available'],
]);

it('rejects invalid product update input', function (array $payload, string $field) {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)
        ->from(route('products.edit', $product))
        ->put(route('products.update', $product), array_merge([
            'title' => 'Valid Product',
            'description' => '<p>Useful product description.</p>',
            'price' => '10.00',
            'date_available' => '2026-07-01',
        ], $payload));

    $response
        ->assertRedirect(route('products.edit', $product))
        ->assertSessionHasErrors($field);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'missing description' => [['description' => ''], 'description'],
    'invalid price' => [['price' => '-1'], 'price'],
    'invalid date' => [['date_available' => 'tomorrow'], 'date_available'],
]);
