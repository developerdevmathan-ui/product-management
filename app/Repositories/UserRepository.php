<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserReadRepositoryInterface;
use App\Repositories\Contracts\UserWriteRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserReadRepositoryInterface, UserWriteRepositoryInterface
{
    /**
     * Get users for administrator role management.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateForRoleManagement(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->orderBy('role')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Count users with a given role.
     */
    public function countByRole(UserRole $role): int
    {
        return User::query()
            ->where('role', $role->value)
            ->count();
    }

    /**
     * Persist a role change for the user.
     */
    public function updateRole(User $user, UserRole $role): User
    {
        $user->forceFill([
            'role' => $role,
        ])->save();

        return $user->refresh();
    }
}
