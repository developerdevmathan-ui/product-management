<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Closure;
use Illuminate\Database\QueryException;

interface ProductWriteRepositoryInterface
{
    /**
     * Persist a new product.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product;

    /**
     * Persist changes to an existing product.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product;

    /**
     * Delete a product.
     */
    public function delete(Product $product): void;

    /**
     * Run product persistence work in a database transaction.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function transaction(Closure $callback, int $attempts = 1): mixed;

    /**
     * Determine whether an exception came from the product SKU unique constraint.
     */
    public function isSkuUniqueConstraintViolation(QueryException $exception): bool;
}
