<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

describe('password reset', function () {
    it('shows the forgot password form from the login page', function () {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('password.request'));

        $this->get('/forgot-password')->assertOk()->assertSee('Lupa kata sandi');
    });

    it('sends a reset password link', function () {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('resets the password with a valid token', function () {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    });

    it('rejects an invalid reset token', function () {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors(['email']);
    });
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
