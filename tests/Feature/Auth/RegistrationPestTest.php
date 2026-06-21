<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('registers a new standard user with valid data', function () {
    Event::fake();

    $response = $this->post(route('register'), [
        'name' => 'QA User',
        'email' => 'qa@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    $user = User::where('email', 'qa@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect($user->isStandardUser())->toBeTrue()
        ->and(Hash::check('SecurePass123!', $user->password))->toBeTrue();

    Event::assertDispatched(Registered::class);
    $response->assertRedirect(route('dashboard', absolute: false));
});

it('rejects registration with invalid email', function () {
    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'QA User',
        'email' => 'invalid-email',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    $response
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects registration with a weak password', function () {
    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'QA User',
        'email' => 'qa@example.com',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $response
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('password');

    $this->assertGuest();
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'QA User',
        'email' => 'taken@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    $response
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
