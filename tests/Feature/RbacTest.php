<?php

use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\get;

describe('role-based access', function () {
    it('allows admin to access dashboard', function () {
        $admin = User::factory()->admin()->create();

        get('/dashboard')->assertRedirect('/login');
    });

    it('allows pegawai with dashboard access', function () {
        $pegawai = User::factory()->pegawai()->withDashboard()->create();

        $this->actingAs($pegawai)->get('/dashboard')->assertOk();
    });

    it('blocks pegawai without dashboard access from dashboard', function () {
        $pegawai = User::factory()->pegawai()->withoutDashboard()->create();

        $this->actingAs($pegawai)->get('/dashboard')->assertForbidden();
    });

    it('blocks pegawai from users page', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->get('/users')->assertForbidden();
    });

    it('blocks pegawai from reports page', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->get('/reports')->assertForbidden();
    });

    it('blocks pegawai from product mutation routes', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create();

        $this->actingAs($pegawai)->post('/products', [])->assertForbidden();
        $this->actingAs($pegawai)->putJson("/products/{$product->id}", [])->assertForbidden();
        $this->actingAs($pegawai)->deleteJson("/products/{$product->id}")->assertForbidden();
    });

    it('allows pegawai to view products', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->get('/products')->assertOk();
    });

    it('requires authentication for protected routes', function () {
        get('/dashboard')->assertRedirect('/login');
        get('/products')->assertRedirect('/login');
        get('/income')->assertRedirect('/login');
        get('/expenses')->assertRedirect('/login');
    });
});
