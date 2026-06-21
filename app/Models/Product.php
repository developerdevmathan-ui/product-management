<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['title', 'description', 'price', 'date_available'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'date_available' => 'date',
        ];
    }

    /**
     * Scope a query to products matching a search keyword.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = Str::of((string) $keyword)
            ->squish()
            ->lower()
            ->limit(100, '')
            ->toString();

        if ($keyword === '') {
            return $query;
        }

        $like = '%'.$this->escapeLike($keyword).'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query
                ->whereRaw("LOWER(title) LIKE ? ESCAPE '\\\\'", [$like])
                ->orWhereRaw("LOWER(description) LIKE ? ESCAPE '\\\\'", [$like]);
        });
    }

    /**
     * Scope a query to products within a price range.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePriceBetween(Builder $query, mixed $minimum = null, mixed $maximum = null): Builder
    {
        return $query
            ->when($minimum !== null && $minimum !== '', fn (Builder $query): Builder => $query->where('price', '>=', $minimum))
            ->when($maximum !== null && $maximum !== '', fn (Builder $query): Builder => $query->where('price', '<=', $maximum));
    }

    /**
     * Scope a query to products available on a specific date.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeAvailableOn(Builder $query, ?string $date): Builder
    {
        if ($date === null || $date === '') {
            return $query;
        }

        return $query->whereDate('date_available', $date);
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
