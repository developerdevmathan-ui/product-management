<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const SEEDED_PRODUCT_COUNT = 100;

    /**
     * Seed the application's database.
     */
    public function run(ProductService $products): void
    {
        // User::factory(10)->create();

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => UserRole::Admin,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Normal User',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => UserRole::User,
            ],
        );

        $this->seedProducts($products);
    }

    private function seedProducts(ProductService $products): void
    {
        $productsToCreate = max(0, self::SEEDED_PRODUCT_COUNT - Product::query()->count());

        for ($index = 1; $index <= $productsToCreate; $index++) {
            $sequence = Product::query()->count() + 1;
            $quantity = $sequence % 5 === 0 ? 0 : ($sequence * 3) % 250;

            $products->create([
                'title' => sprintf('Seeded Product %03d', $sequence),
                'description' => sprintf(
                    '<p>Production-safe seeded product description for catalog item %03d.</p>',
                    $sequence,
                ),
                'price' => number_format(25 + ($sequence * 11.35), 2, '.', ''),
                'quantity' => $quantity,
                'date_available' => now()->addDays($sequence % 45)->toDateString(),
            ]);
        }
    }
}
