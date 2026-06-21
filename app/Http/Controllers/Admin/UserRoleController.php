<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserRoleController extends Controller
{
    /**
     * Display users for administrator role management.
     */
    public function index(): View
    {
        Gate::authorize('manageUsers', User::class);

        return view('admin.users.index', [
            'users' => User::query()
                ->orderByRaw('role = ? desc', [UserRole::Admin->value])
                ->orderBy('name')
                ->paginate(15),
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

        if ($role !== UserRole::Admin && $user->isAdmin() && User::where('role', UserRole::Admin->value)->count() <= 1) {
            return back()->withErrors([
                'role' => __('At least one administrator account is required.'),
            ]);
        }

        $user->forceFill([
            'role' => $role,
        ])->save();

        return back()->with('status', 'user-role-updated');
    }
}
