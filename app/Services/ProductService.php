<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class ProductService
{
    /**
     * Get the paginated product listing.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->search($filters['q'] ?? null)
            ->priceBetween($filters['price_min'] ?? null, $filters['price_max'] ?? null)
            ->availableOn($filters['date_available'] ?? null)
            ->latest('date_available')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a product.
     *
     * @param  array{title: string, description: string, price: numeric-string|float|int, date_available: string}  $data
     */
    public function create(array $data): Product
    {
        return Product::create($this->payload($data));
    }

    /**
     * Update a product.
     *
     * @param  array{title: string, description: string, price: numeric-string|float|int, date_available: string}  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($this->payload($data));

        return $product->refresh();
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Restrict persistence to product-owned fields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return Arr::only($data, [
            'title',
            'description',
            'price',
            'date_available',
        ]);
    }
}
