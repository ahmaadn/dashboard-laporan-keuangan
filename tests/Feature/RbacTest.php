<?php

use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\get;

describe('role-based access', function () {
    it('allows admin to access dashboard', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
    });

    it('blocks pegawai from dashboard', function () {
        $pegawai = User::factory()->pegawai()->create();

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

    it('allows pegawai to mutate products and stock', function () {
        $pegawai = User::factory()->pegawai()->create();
        $updatedProduct = Product::factory()->create();
        $stockedProduct = Product::factory()->create(['stok' => 10]);
        $deletedProduct = Product::factory()->create();

        $this->actingAs($pegawai)->putJson("/products/{$updatedProduct->id}", [
            'nama' => 'Produk Diperbarui Pegawai',
            'harga' => $updatedProduct->harga,
        ])->assertOk();
        $this->actingAs($pegawai)->postJson("/products/{$stockedProduct->id}/stock", [
            'aksi' => 'restok',
            'jumlah' => 5,
        ])->assertOk();
        $this->actingAs($pegawai)->deleteJson("/products/{$deletedProduct->id}")->assertOk();

        expect($updatedProduct->fresh()->nama)->toBe('Produk Diperbarui Pegawai')
            ->and($stockedProduct->fresh()->stok)->toBe(15)
            ->and($deletedProduct->fresh()->trashed())->toBeTrue();
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
