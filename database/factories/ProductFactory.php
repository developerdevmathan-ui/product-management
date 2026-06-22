<?php

namespace Database\Factories;

use App\Enums\StockStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Configure the factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Product $product): void {
            $product->stock_status ??= StockStatus::fromQuantity((int) $product->quantity);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => $this->faker->unique()->numerify('PRD-######'),
            'title' => $this->faker->words(3, true),
            'description' => '<p>'.$this->faker->paragraph().'</p>',
            'price' => $this->faker->randomFloat(2, 1, 9999),
            'quantity' => $this->faker->numberBetween(0, 250),
            'date_available' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
        ];
    }
}
