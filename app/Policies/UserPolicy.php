<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the administrator can manage another user's role.
     */
    public function manageRoles(User $administrator, User $user): bool
    {
        return $administrator->isAdmin() && ! $administrator->is($user);
    }

    /**
     * Determine whether the user can manage user accounts.
     */
    public function manageUsers(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can access the admin dashboard.
     */
    public function accessAdminDashboard(User $user): bool
    {
        return $user->isAdmin();
    }
}
