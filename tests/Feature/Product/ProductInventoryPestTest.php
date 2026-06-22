<?php

use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks a created product as out of stock when quantity is zero', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('products.store'), [
            'sku' => 'PRD-000701',
            'title' => 'Zero Quantity Product',
            'description' => '<p>No units are available.</p>',
            'price' => '25.00',
            'quantity' => '0',
            'stock_status' => StockStatus::InStock->value,
            'date_available' => '2026-07-01',
        ])
        ->assertSessionHasNoErrors();

    $product = Product::where('sku', 'PRD-000701')->firstOrFail();

    expect($product->quantity)->toBe(0)
        ->and($product->stock_status)->toBe(StockStatus::OutOfStock);
});

it('marks a created product as in stock when quantity is greater than zero', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('products.store'), [
            'sku' => 'PRD-000702',
            'title' => 'Positive Quantity Product',
            'description' => '<p>Units are available.</p>',
            'price' => '25.00',
            'quantity' => '4',
            'stock_status' => StockStatus::OutOfStock->value,
            'date_available' => '2026-07-01',
        ])
        ->assertSessionHasNoErrors();

    $product = Product::where('sku', 'PRD-000702')->firstOrFail();

    expect($product->quantity)->toBe(4)
        ->and($product->stock_status)->toBe(StockStatus::InStock);
});

it('recomputes stock status when product quantity changes', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create([
        'sku' => 'PRD-000703',
        'quantity' => 10,
    ]);

    $this->actingAs($admin)
        ->put(route('products.update', $product), [
            'sku' => $product->sku,
            'title' => $product->title,
            'description' => $product->description,
            'price' => $product->price,
            'quantity' => '0',
            'stock_status' => StockStatus::InStock->value,
            'date_available' => $product->date_available->format('Y-m-d'),
        ])
        ->assertSessionHasNoErrors();

    expect($product->refresh()->stock_status)->toBe(StockStatus::OutOfStock);
});
