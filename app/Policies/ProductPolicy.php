<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can view the product listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStandardUser();
    }

    /**
     * Determine whether the user can view a product.
     */
    public function view(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isStandardUser();
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update products.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete products.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
