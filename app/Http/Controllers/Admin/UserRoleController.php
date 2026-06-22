<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use App\Services\UserRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly UserRoleService $userRoles,
    ) {}

    /**
     * Display users for administrator role management.
     */
    public function index(): View
    {
        Gate::authorize('manageUsers', User::class);

        return view('admin.users.index', [
            'users' => $this->userRoles->paginateUsers(),
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Update a user's administrator status.
     */
    public function update(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manageRoles', $user);

        $validated = $request->validated();

        $role = UserRole::from($validated['role']);

        $this->userRoles->updateRole($user, $role);

        return back()->with('status', 'user-role-updated');
    }
}
