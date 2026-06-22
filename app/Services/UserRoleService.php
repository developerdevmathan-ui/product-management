<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserReadRepositoryInterface;
use App\Repositories\Contracts\UserWriteRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class UserRoleService
{
    public function __construct(
        private readonly UserReadRepositoryInterface $userReader,
        private readonly UserWriteRepositoryInterface $userWriter,
    ) {}

    /**
     * Get users for administrator role management.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userReader->paginateForRoleManagement($perPage);
    }

    /**
     * Change a user's role while preserving required administrator access.
     *
     * @throws ValidationException
     */
    public function updateRole(User $user, UserRole $role): User
    {
        if ($this->wouldRemoveLastAdministrator($user, $role)) {
            throw ValidationException::withMessages([
                'role' => __('At least one administrator account is required.'),
            ]);
        }

        return $this->userWriter->updateRole($user, $role);
    }

    /**
     * Determine whether the change would leave the system without an administrator.
     */
    private function wouldRemoveLastAdministrator(User $user, UserRole $role): bool
    {
        return $role !== UserRole::Admin
            && $user->isAdmin()
            && $this->userReader->countByRole(UserRole::Admin) <= 1;
    }
}
