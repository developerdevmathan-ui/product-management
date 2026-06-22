<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters products by price range', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Budget Product', 'price' => '9.99']);
    Product::factory()->create(['title' => 'Midrange Product', 'price' => '49.99']);
    Product::factory()->create(['title' => 'Premium Product', 'price' => '199.99']);

    $this->actingAs($user)
        ->get(route('products.index', [
            'min_price' => '20',
            'max_price' => '100',
        ]))
        ->assertOk()
        ->assertSee('Midrange Product')
        ->assertDontSee('Budget Product')
        ->assertDontSee('Premium Product');
});

it('filters products by quantity range', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Low Quantity Product', 'quantity' => 3]);
    Product::factory()->create(['title' => 'Target Quantity Product', 'quantity' => 10]);
    Product::factory()->create(['title' => 'High Quantity Product', 'quantity' => 30]);

    $this->actingAs($user)
        ->get(route('products.index', [
            'min_quantity' => '5',
            'max_quantity' => '20',
        ]))
        ->assertOk()
        ->assertSee('Target Quantity Product')
        ->assertDontSee('Low Quantity Product')
        ->assertDontSee('High Quantity Product');
});

it('filters products by available date range', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Early Product', 'date_available' => '2026-06-01']);
    Product::factory()->create(['title' => 'Window Product', 'date_available' => '2026-07-15']);
    Product::factory()->create(['title' => 'Future Product', 'date_available' => '2026-09-01']);

    $this->actingAs($user)
        ->get(route('products.index', [
            'date_from' => '2026-07-01',
            'date_to' => '2026-08-01',
        ]))
        ->assertOk()
        ->assertSee('Window Product')
        ->assertDontSee('Early Product')
        ->assertDontSee('Future Product');
});

it('filters products by stock status', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Available Product', 'quantity' => 10]);
    Product::factory()->create(['title' => 'Unavailable Product', 'quantity' => 0]);

    $this->actingAs($user)
        ->get(route('products.index', ['stock_status' => 'in_stock']))
        ->assertOk()
        ->assertSee('Available Product')
        ->assertDontSee('Unavailable Product');
});

it('combines search and filters', function () {
    $user = User::factory()->create();

    Product::factory()->create([
        'title' => 'Laptop Basic',
        'description' => '<p>Portable computer.</p>',
        'price' => '49.99',
        'quantity' => 5,
        'date_available' => '2026-07-01',
    ]);
    Product::factory()->create([
        'title' => 'Laptop Premium',
        'description' => '<p>Portable computer.</p>',
        'price' => '149.99',
        'quantity' => 5,
        'date_available' => '2026-07-01',
    ]);
    Product::factory()->create([
        'title' => 'Monitor Basic',
        'description' => '<p>External display.</p>',
        'price' => '49.99',
        'quantity' => 0,
        'date_available' => '2026-07-01',
    ]);

    $this->actingAs($user)
        ->get(route('products.index', [
            'q' => 'laptop',
            'min_price' => '20',
            'max_price' => '100',
            'stock_status' => 'in_stock',
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-01',
        ]))
        ->assertOk()
        ->assertSee('Laptop Basic')
        ->assertDontSee('Laptop Premium')
        ->assertDontSee('Monitor Basic');
});

it('sorts products by price descending', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Budget Product', 'price' => '9.99']);
    Product::factory()->create(['title' => 'Premium Product', 'price' => '199.99']);
    Product::factory()->create(['title' => 'Midrange Product', 'price' => '49.99']);

    $this->actingAs($user)
        ->get(route('products.index', ['sort' => 'price_desc']))
        ->assertOk()
        ->assertSeeInOrder([
            'Premium Product',
            'Midrange Product',
            'Budget Product',
        ]);
});

it('sorts products by quantity descending', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Small Stock Product', 'quantity' => 2]);
    Product::factory()->create(['title' => 'Large Stock Product', 'quantity' => 50]);
    Product::factory()->create(['title' => 'Medium Stock Product', 'quantity' => 10]);

    $this->actingAs($user)
        ->get(route('products.index', ['sort' => 'quantity_desc']))
        ->assertOk()
        ->assertSeeInOrder([
            'Large Stock Product',
            'Medium Stock Product',
            'Small Stock Product',
        ]);
});

it('sorts products by title ascending', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Zenith Product']);
    Product::factory()->create(['title' => 'Atlas Product']);
    Product::factory()->create(['title' => 'Beacon Product']);

    $this->actingAs($user)
        ->get(route('products.index', ['sort' => 'title_asc']))
        ->assertOk()
        ->assertSeeInOrder([
            'Atlas Product',
            'Beacon Product',
            'Zenith Product',
        ]);
});
