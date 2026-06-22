<?php

namespace App\Enums;

enum StockStatus: string
{
    case InStock = 'in_stock';
    case OutOfStock = 'out_of_stock';

    /**
     * Resolve stock status from the authoritative inventory count.
     */
    public static function fromQuantity(int $quantity): self
    {
        return $quantity > 0 ? self::InStock : self::OutOfStock;
    }

    /**
     * Get the human-readable stock status label.
     */
    public function label(): string
    {
        return match ($this) {
            self::InStock => 'In Stock',
            self::OutOfStock => 'Out Of Stock',
        };
    }
}
