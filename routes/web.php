<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('products', ProductController::class)
        ->only(['create', 'store'])
        ->middleware('can:create,'.Product::class);

    Route::resource('products', ProductController::class)
        ->only(['index', 'show']);

    Route::resource('products', ProductController::class)
        ->only(['edit', 'update'])
        ->middleware('can:update,product');

    Route::resource('products', ProductController::class)
        ->only(['destroy'])
        ->middleware('can:delete,product');
});

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/users', [UserRoleController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserRoleController::class, 'update'])->name('users.role.update');
    });

require __DIR__.'/auth.php';
