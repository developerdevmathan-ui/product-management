<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a standard user to access their own dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('allows a standard user to manage their own profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh())
        ->name->toBe('Updated User')
        ->email->toBe('updated@example.com');
});

it('redirects guests away from protected user routes', function (string $method, string $uri) {
    $this->{$method}($uri)->assertRedirect(route('login'));
})->with([
    'dashboard' => ['get', '/dashboard'],
    'profile edit' => ['get', '/profile'],
    'profile update' => ['patch', '/profile'],
    'profile delete' => ['delete', '/profile'],
]);

it('returns 403 when a standard user attempts to access admin routes', function (string $method, string $uri) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->{$method}($uri)
        ->assertForbidden();
})->with([
    'admin dashboard' => ['get', '/admin/dashboard'],
    'admin users' => ['get', '/admin/users'],
]);
