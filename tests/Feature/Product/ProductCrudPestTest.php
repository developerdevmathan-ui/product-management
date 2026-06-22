<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists products for a standard user', function () {
    $user = User::factory()->create();

    Product::factory()->create([
        'sku' => 'PRD-000101',
        'title' => 'Roadmap Planner',
        'quantity' => 8,
    ]);
    Product::factory()->create([
        'sku' => 'PRD-000102',
        'title' => 'Release Tracker',
        'quantity' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('PRD-000101')
        ->assertSee('Roadmap Planner')
        ->assertSee('In Stock')
        ->assertSee('PRD-000102')
        ->assertSee('Release Tracker')
        ->assertSee('Out Of Stock');
});

it('shows product details for a standard user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'title' => 'Discovery Workspace',
        'description' => '<p>Research and planning workspace.</p>',
        'price' => '49.99',
        'quantity' => 7,
        'date_available' => '2026-07-15',
    ]);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Discovery Workspace')
        ->assertSee($product->sku)
        ->assertSee('49.99')
        ->assertSee('7')
        ->assertSee('In Stock')
        ->assertSee('Research and planning workspace.');
});

it('creates a product as an admin', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->from(route('products.create'))
        ->post(route('products.store'), [
            'sku' => 'PRD-000501',
            'title' => 'Secure Product',
            'description' => '<p>Safe copy</p><script>alert(1)</script><a href="javascript:alert(1)">bad link</a>',
            'price' => '19.99',
            'quantity' => '0',
            'stock_status' => 'in_stock',
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
        ->and($product->sku)->toBe('PRD-000501')
        ->and($product->price)->toBe('19.99')
        ->and($product->quantity)->toBe(0)
        ->and($product->stock_status->value)->toBe('out_of_stock')
        ->and($product->date_available->toDateString())->toBe('2026-07-01');
});

it('removes ckeditor chrome from product descriptions', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->from(route('products.create'))
        ->post(route('products.store'), [
            'sku' => 'PRD-000502',
            'title' => 'Clean Rich Text Product',
            'description' => '<p>Visible product copy.</p><div class="ck ck-balloon-panel ck-balloon-panel_visible ck-powered-by-balloon"><div class="ck ck-powered-by"><a href="https://ckeditor.com/">Powered by CKEditor</a></div></div>',
            'price' => '29.99',
            'quantity' => '3',
            'date_available' => '2026-07-01',
        ]);

    $product = Product::where('title', 'Clean Rich Text Product')->firstOrFail();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.show', $product));

    expect($product->description)
        ->toContain('Visible product copy.')
        ->not->toContain('ck-balloon-panel')
        ->not->toContain('ck-powered-by')
        ->not->toContain('Powered by CKEditor');

    $this->actingAs($admin)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Visible product copy.')
        ->assertDontSee('Powered by CKEditor');
});

it('updates a product as an admin', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['sku' => 'PRD-000601']);

    $this->actingAs($admin)
        ->from(route('products.edit', $product))
        ->put(route('products.update', $product), [
            'sku' => 'PRD-000602',
            'title' => 'Updated Product',
            'description' => '<h2>Updated</h2><img src=x onerror=alert(1)>',
            'price' => '29.95',
            'quantity' => '12',
            'stock_status' => 'out_of_stock',
            'date_available' => '2026-08-15',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.show', $product));

    expect($product->refresh())
        ->sku->toBe('PRD-000602')
        ->title->toBe('Updated Product')
        ->description->toContain('<h2>Updated</h2>')
        ->description->not->toContain('<img')
        ->price->toBe('29.95')
        ->quantity->toBe(12)
        ->stock_status->value->toBe('in_stock')
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
