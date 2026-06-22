<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Repositories\Contracts\UserReadRepositoryInterface;

class AdminDashboardService
{
    public function __construct(
        private readonly UserReadRepositoryInterface $users,
    ) {}

    /**
     * Get administrator dashboard metrics.
     *
     * @return array{adminCount: int, userCount: int}
     */
    public function metrics(): array
    {
        return [
            'adminCount' => $this->users->countByRole(UserRole::Admin),
            'userCount' => $this->users->countByRole(UserRole::User),
        ];
    }
}
