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
            'price_min' => '20',
            'price_max' => '100',
        ]))
        ->assertOk()
        ->assertSee('Midrange Product')
        ->assertDontSee('Budget Product')
        ->assertDontSee('Premium Product');
});

it('filters products by available date', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Today Product', 'date_available' => '2026-07-01']);
    Product::factory()->create(['title' => 'Future Product', 'date_available' => '2026-08-01']);

    $this->actingAs($user)
        ->get(route('products.index', ['date_available' => '2026-07-01']))
        ->assertOk()
        ->assertSee('Today Product')
        ->assertDontSee('Future Product');
});

it('combines search and filters', function () {
    $user = User::factory()->create();

    Product::factory()->create([
        'title' => 'Laptop Basic',
        'description' => '<p>Portable computer.</p>',
        'price' => '49.99',
        'date_available' => '2026-07-01',
    ]);
    Product::factory()->create([
        'title' => 'Laptop Premium',
        'description' => '<p>Portable computer.</p>',
        'price' => '149.99',
        'date_available' => '2026-07-01',
    ]);
    Product::factory()->create([
        'title' => 'Monitor Basic',
        'description' => '<p>External display.</p>',
        'price' => '49.99',
        'date_available' => '2026-07-01',
    ]);

    $this->actingAs($user)
        ->get(route('products.index', [
            'q' => 'laptop',
            'price_min' => '20',
            'price_max' => '100',
            'date_available' => '2026-07-01',
        ]))
        ->assertOk()
        ->assertSee('Laptop Basic')
        ->assertDontSee('Laptop Premium')
        ->assertDontSee('Monitor Basic');
});
