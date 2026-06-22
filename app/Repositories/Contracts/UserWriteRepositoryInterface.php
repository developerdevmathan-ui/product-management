<?php

namespace App\Repositories\Contracts;

use App\Enums\UserRole;
use App\Models\User;

interface UserWriteRepositoryInterface
{
    /**
     * Persist a role change for the user.
     */
    public function updateRole(User $user, UserRole $role): User;
}
