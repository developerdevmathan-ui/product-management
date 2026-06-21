<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the administrator dashboard.
     */
    public function __invoke(): View
    {
        Gate::authorize('accessAdminDashboard', User::class);

        return view('admin.dashboard', [
            'adminCount' => User::where('role', UserRole::Admin->value)->count(),
            'userCount' => User::where('role', UserRole::User->value)->count(),
        ]);
    }
}
