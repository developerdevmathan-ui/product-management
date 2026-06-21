<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('searches products by title', function () {
    $user = User::factory()->create();

    Product::factory()->create(['title' => 'Laptop Stand']);
    Product::factory()->create(['title' => 'Desk Lamp']);

    $this->actingAs($user)
        ->get(route('products.index', ['q' => 'laptop']))
        ->assertOk()
        ->assertSee('Laptop Stand')
        ->assertDontSee('Desk Lamp');
});

it('searches products by description', function () {
    $user = User::factory()->create();

    Product::factory()->create([
        'title' => 'Planning Kit',
        'description' => '<p>Includes laptop accessories and cables.</p>',
    ]);
    Product::factory()->create([
        'title' => 'Writing Kit',
        'description' => '<p>Includes notebooks and pens.</p>',
    ]);

    $this->actingAs($user)
        ->get(route('products.index', ['q' => 'ACCESSORIES']))
        ->assertOk()
        ->assertSee('Planning Kit')
        ->assertDontSee('Writing Kit');
});

it('preserves the search query during pagination', function () {
    $user = User::factory()->create();

    Product::factory()
        ->count(16)
        ->sequence(fn (Sequence $sequence) => [
            'title' => 'Laptop Item '.$sequence->index,
            'description' => '<p>Portable hardware.</p>',
        ])
        ->create();

    $this->actingAs($user)
        ->get(route('products.index', ['q' => 'laptop']))
        ->assertOk()
        ->assertSee('q=laptop', false);
});
