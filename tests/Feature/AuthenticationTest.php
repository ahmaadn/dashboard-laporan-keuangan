<?php

use App\Models\User;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;

describe('login', function () {
    it('redirects to dashboard for admin on success', function () {
        $admin = User::factory()->admin()->create(['password' => 'password']);

        $response = post('/login', ['login' => $admin->username, 'password' => 'password']);

        $response->assertRedirect('/dashboard');
        assertAuthenticated();
    });

    it('redirects to dashboard for pegawai with dashboard access', function () {
        $pegawai = User::factory()->pegawai()->withDashboard()->create(['password' => 'password']);

        $response = post('/login', ['login' => $pegawai->username, 'password' => 'password']);

        $response->assertRedirect('/dashboard');
    });

    it('redirects to income for pegawai without dashboard access', function () {
        $pegawai = User::factory()->pegawai()->withoutDashboard()->create(['password' => 'password']);

        $response = post('/login', ['login' => $pegawai->username, 'password' => 'password']);

        $response->assertRedirect('/income');
    });

    it('accepts email as login field', function () {
        $admin = User::factory()->admin()->create(['password' => 'password']);

        $response = post('/login', ['login' => $admin->email, 'password' => 'password']);

        $response->assertRedirect('/dashboard');
        assertAuthenticated();
    });

    it('shows error for invalid credentials', function () {
        $admin = User::factory()->admin()->create();

        $response = post('/login', ['login' => $admin->username, 'password' => 'wrong']);

        $response->assertSessionHasErrors(['login']);
        assertGuest();
    });

    it('shows error for nonexistent user', function () {
        $response = post('/login', ['login' => 'ghost', 'password' => 'password']);

        $response->assertSessionHasErrors(['login']);
        assertGuest();
    });

    it('blocks inactive account', function () {
        $user = User::factory()->admin()->inactive()->create(['password' => 'password']);

        $response = post('/login', ['login' => $user->username, 'password' => 'password']);

        $response->assertSessionHasErrors(['login']);
        assertGuest();
    });

    it('blocks soft-deleted account', function () {
        $user = User::factory()->admin()->create(['password' => 'password']);
        $user->delete();

        $response = post('/login', ['login' => $user->username, 'password' => 'password']);

        $response->assertSessionHasErrors(['login']);
        assertGuest();
    });

    it('validates required fields', function () {
        $response = post('/login', []);

        $response->assertSessionHasErrors(['login', 'password']);
    });
});

describe('logout', function () {
    it('logs out and redirects to login', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/logout');

        $response->assertRedirect('/login');
        assertGuest();
    });
});
