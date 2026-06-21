<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a registered user to login with valid credentials', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

it('rejects login with invalid credentials', function () {
    $user = User::factory()->create();

    $response = $this->from(route('login'))->post(route('login'), [
        'email' => $user->email,
        'password' => 'invalid-password',
    ]);

    $this->assertGuest();
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

it('validates login email format', function () {
    $response = $this->from(route('login'))->post(route('login'), [
        'email' => 'not-an-email',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

it('logs out an authenticated user and invalidates the session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect('/');
});
