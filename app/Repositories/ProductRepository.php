<?php

namespace App\Repositories;

use App\Enums\ProductSort;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Get paginated products for the listing page.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->when($this->searchKeyword($filters['search'] ?? null) !== '', function (Builder $query) use ($filters): void {
                $like = '%'.$this->escapeLike($this->searchKeyword($filters['search'] ?? null)).'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->whereLike('sku', $like)
                        ->orWhereLike('title', $like)
                        ->orWhereLike('description', $like);
                });
            })
            ->when(filled($filters['stock_status'] ?? null), fn (Builder $query): Builder => $query->where('stock_status', $filters['stock_status']))
            ->when(filled($filters['min_price'] ?? null), fn (Builder $query): Builder => $query->where('price', '>=', $filters['min_price']))
            ->when(filled($filters['max_price'] ?? null), fn (Builder $query): Builder => $query->where('price', '<=', $filters['max_price']))
            ->when(filled($filters['min_quantity'] ?? null), fn (Builder $query): Builder => $query->where('quantity', '>=', $filters['min_quantity']))
            ->when(filled($filters['max_quantity'] ?? null), fn (Builder $query): Builder => $query->where('quantity', '<=', $filters['max_quantity']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query): Builder => $query->whereDate('date_available', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query): Builder => $query->whereDate('date_available', '<=', $filters['date_to']))
            ->tap(fn (Builder $query) => $this->applySort($query, $filters['sort'] ?? 'latest'))
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Persist a new product.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        $product = new Product;
        $product->forceFill($data)->save();

        return $product;
    }

    /**
     * Persist changes to an existing product.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->forceFill($data)->save();

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
     * Get the latest SKU in the product sequence.
     */
    public function latestSku(bool $lockForUpdate = false): ?string
    {
        $query = Product::query()
            ->where('sku', 'like', 'PRD-%')
            ->orderByDesc('sku');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $sku = $query->value('sku');

        return is_string($sku) ? $sku : null;
    }

    /**
     * Determine whether a SKU already exists.
     */
    public function skuExists(string $sku, ?int $ignoreProductId = null): bool
    {
        return Product::query()
            ->where('sku', $sku)
            ->when($ignoreProductId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreProductId))
            ->exists();
    }

    /**
     * Run product persistence work in a database transaction.
     */
    public function transaction(Closure $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }

    /**
     * Determine whether an exception came from the product SKU unique constraint.
     */
    public function isSkuUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        $message = $exception->getMessage();

        return in_array($sqlState, ['23000', '23505'], true)
            || $driverCode === '1062'
            || str_contains($message, 'products_sku_unique')
            || str_contains($message, 'products.sku');
    }

    /**
     * Apply a whitelisted product listing sort.
     */
    private function applySort(Builder $query, ?string $sort): void
    {
        match (ProductSort::tryFrom((string) $sort)) {
            ProductSort::Oldest => $query->oldest()->oldest('id'),
            ProductSort::PriceAsc => $query->orderBy('price')->orderBy('id'),
            ProductSort::PriceDesc => $query->orderByDesc('price')->orderByDesc('id'),
            ProductSort::QuantityDesc => $query->orderByDesc('quantity')->orderByDesc('id'),
            ProductSort::TitleAsc => $query->orderBy('title')->orderBy('id'),
            default => $query->latest()->latest('id'),
        };
    }

    /**
     * Normalize search input for SQL LIKE matching.
     */
    private function searchKeyword(mixed $keyword): string
    {
        return Str::of((string) $keyword)
            ->squish()
            ->lower()
            ->limit(100, '')
            ->toString();
    }

    /**
     * Escape LIKE wildcards in user-provided search text.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value,
        );
    }
}
