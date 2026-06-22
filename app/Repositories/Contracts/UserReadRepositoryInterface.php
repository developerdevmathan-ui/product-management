<?php

namespace App\Repositories\Contracts;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserReadRepositoryInterface
{
    /**
     * Get users for administrator role management.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateForRoleManagement(int $perPage = 15): LengthAwarePaginator;

    /**
     * Count users with a given role.
     */
    public function countByRole(UserRole $role): int;
}
