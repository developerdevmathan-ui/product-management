<?php

namespace App\Enums;

enum ProductSort: string
{
    case Latest = 'latest';
    case Oldest = 'oldest';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
    case QuantityDesc = 'quantity_desc';
    case TitleAsc = 'title_asc';

    /**
     * Get the human-readable sort label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Latest => 'Latest',
            self::Oldest => 'Oldest',
            self::PriceAsc => 'Price Low To High',
            self::PriceDesc => 'Price High To Low',
            self::QuantityDesc => 'Quantity High To Low',
            self::TitleAsc => 'Title A-Z',
        };
    }

    /**
     * Get allowed request values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $sort): string => $sort->value,
            self::cases(),
        );
    }

    /**
     * Get value => label options for views.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $options, self $sort): array {
                $options[$sort->value] = $sort->label();

                return $options;
            },
            [],
        );
    }
}
