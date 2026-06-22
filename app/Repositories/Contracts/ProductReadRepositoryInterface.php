<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductReadRepositoryInterface
{
    /**
     * Get paginated products for the listing page.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get the latest SKU in the product sequence.
     */
    public function latestSku(bool $lockForUpdate = false): ?string;

    /**
     * Determine whether a SKU already exists.
     */
    public function skuExists(string $sku, ?int $ignoreProductId = null): bool;
}
