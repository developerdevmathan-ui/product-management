<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboard,
    ) {}

    /**
     * Display the administrator dashboard.
     */
    public function __invoke(): View
    {
        Gate::authorize('accessAdminDashboard', User::class);

        return view('admin.dashboard', $this->dashboard->metrics());
    }
}
