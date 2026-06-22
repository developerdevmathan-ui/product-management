<?php

namespace App\Services;

use App\Enums\StockStatus;
use App\Models\Product;
use App\Repositories\Contracts\ProductReadRepositoryInterface;
use App\Repositories\Contracts\ProductWriteRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProductService
{
    public function __construct(
        private readonly ProductReadRepositoryInterface $productReader,
        private readonly ProductWriteRepositoryInterface $productWriter,
    ) {}

    /**
     * Get the paginated product listing.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->productReader->paginate($filters, $perPage);
    }

    /**
     * Create an empty product instance for form binding.
     */
    public function make(): Product
    {
        return new Product;
    }

    /**
     * Create a product.
     *
     * @param  array{sku?: string|null, title: string, description: string, price: numeric-string|float|int, quantity: int|string, date_available: string}  $data
     */
    public function create(array $data): Product
    {
        $payload = $this->payload($data, includeBlankSku: true);

        if (filled($payload['sku'] ?? null)) {
            $this->ensureSkuIsAvailable((string) $payload['sku']);
        }

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return $this->productWriter->transaction(function () use ($payload): Product {
                    $data = $payload;

                    if (blank($data['sku'] ?? null)) {
                        $data['sku'] = $this->nextSku($this->productReader->latestSku(lockForUpdate: true));
                    }

                    return $this->productWriter->create($data);
                }, 3);
            } catch (QueryException $exception) {
                if (! $this->productWriter->isSkuUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                if (filled($payload['sku'] ?? null)) {
                    $this->throwDuplicateSkuValidationException();
                }

                if ($attempt === 3) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Unable to create product with a unique SKU.');
    }

    /**
     * Update a product.
     *
     * @param  array{sku?: string|null, title: string, description: string, price: numeric-string|float|int, quantity: int|string, date_available: string}  $data
     */
    public function update(Product $product, array $data): Product
    {
        $payload = $this->payload($data, includeBlankSku: false);

        if (blank($payload['sku'] ?? null)) {
            unset($payload['sku']);
        } else {
            $this->ensureSkuIsAvailable((string) $payload['sku'], $product->id);
        }

        try {
            return $this->productWriter->update($product, $payload);
        } catch (QueryException $exception) {
            if (! $this->productWriter->isSkuUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $this->throwDuplicateSkuValidationException();
        }
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product): void
    {
        $this->productWriter->delete($product);
    }

    /**
     * Restrict persistence to product-owned fields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, bool $includeBlankSku): array
    {
        $payload = Arr::only($data, [
            'sku',
            'title',
            'description',
            'price',
            'quantity',
            'date_available',
        ]);

        if (! $includeBlankSku && blank($payload['sku'] ?? null)) {
            unset($payload['sku']);
        }

        $payload['stock_status'] = StockStatus::fromQuantity((int) ($payload['quantity'] ?? 0));

        return $payload;
    }

    /**
     * Generate the next SKU in the PRD-000001 format.
     */
    private function nextSku(?string $latestSku): string
    {
        $latestNumber = 0;

        if (is_string($latestSku) && preg_match('/^PRD-(\d{6})$/', $latestSku, $matches) === 1) {
            $latestNumber = (int) $matches[1];
        }

        $nextNumber = $latestNumber + 1;

        if ($nextNumber > 999999) {
            throw new RuntimeException('Product SKU sequence has been exhausted.');
        }

        return sprintf('PRD-%06d', $nextNumber);
    }

    /**
     * Ensure a manually supplied SKU is unique.
     */
    private function ensureSkuIsAvailable(string $sku, ?int $ignoreProductId = null): void
    {
        if (! $this->productReader->skuExists($sku, $ignoreProductId)) {
            return;
        }

        $this->throwDuplicateSkuValidationException();
    }

    /**
     * Throw a validation exception for duplicate SKU input.
     *
     * @throws ValidationException
     */
    private function throwDuplicateSkuValidationException(): never
    {
        throw ValidationException::withMessages([
            'sku' => __('The SKU has already been taken.'),
        ]);
    }
}
