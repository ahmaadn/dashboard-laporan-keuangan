<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\User;

describe('product index', function () {
    it('shows active products for pegawai', function () {
        $pegawai = User::factory()->pegawai()->create();
        $active = Product::factory()->create(['nama' => 'Dompet Aktif']);
        $trashed = Product::factory()->create(['nama' => 'Dompet Dihapus']);
        $trashed->delete();

        $response = $this->actingAs($pegawai)->get('/products');

        $response->assertOk()
            ->assertSee('Dompet Aktif')
            ->assertDontSee('Dompet Dihapus')
            ->assertSee('Tambah Produk');
    });

    it('shows trashed products for admin', function () {
        $admin = User::factory()->admin()->create();
        $trashed = Product::factory()->create(['nama' => 'Dompet Dihapus']);
        $trashed->delete();

        $this->actingAs($admin)->get('/products')->assertOk()->assertSee('Dompet Dihapus');
    });
});

describe('product store', function () {
    it('creates a product as admin', function () {
        $admin = User::factory()->admin()->create();
        $category = ProductCategory::factory()->create();

        $response = $this->actingAs($admin)->postJson('/products', [
            'nama' => 'Tas Baru',
            'id_kategori' => $category->id,
            'sku' => 'TSL-999',
            'harga' => 300000,
            'deskripsi' => 'Tas baru',
            'aktif' => true,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        expect(Product::where('nama', 'Tas Baru')->exists())->toBeTrue();
        expect(Product::where('nama', 'Tas Baru')->first()->created_by)->toBe($admin->id);
    });

    it('creates a product as pegawai', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/products', [
            'nama' => 'Dompet Pegawai',
            'sku' => 'DMP-001',
            'harga' => 150000,
            'stok' => 5,
        ])->assertCreated()->assertJsonPath('success', true);

        $product = Product::where('nama', 'Dompet Pegawai')->firstOrFail();

        expect($product->created_by)->toBe($pegawai->id)
            ->and($product->stok)->toBe(5)
            ->and(StockMovement::where('product_id', $product->id)->sum('jumlah'))->toBe(5);
    });

    it('validates required fields', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/products', [])->assertStatus(422);
    });

    it('validates unique sku', function () {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['sku' => 'DUP-001']);

        $this->actingAs($admin)->postJson('/products', [
            'nama' => 'Produk',
            'sku' => 'DUP-001',
            'harga' => 100000,
        ])->assertStatus(422);
    });

    it('validates product price relationships', function (array $prices) {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/products', array_merge([
            'nama' => 'Produk Harga Tidak Valid',
            'harga' => 100000,
        ], $prices))->assertUnprocessable()->assertJsonValidationErrors(array_keys($prices));
    })->with([
        'retail below cost' => [['harga_modal' => 100001]],
        'wholesale is zero' => [['harga_grosir' => 0]],
        'wholesale above retail' => [['harga_grosir' => 100001]],
    ]);

    it('allows valid retail, cost, and wholesale prices', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/products', [
            'nama' => 'Produk Harga Valid',
            'harga' => 100000,
            'harga_modal' => 50000,
            'harga_grosir' => 80000,
        ])->assertCreated();
    });

    it('validates unique product name including soft-deleted products', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['nama' => 'Tas Selempang Unik']);
        $product->delete();

        $this->actingAs($admin)->postJson('/products', [
            'nama' => 'Tas Selempang Unik',
            'harga' => 100000,
        ])->assertUnprocessable()->assertJsonValidationErrors(['nama']);
    });

    it('allows null sku', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/products', [
            'nama' => 'No SKU Product',
            'sku' => null,
            'harga' => 50000,
        ])->assertCreated();
    });
});

describe('product update', function () {
    it('updates a product', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['nama' => 'Old Name']);

        $this->actingAs($admin)->putJson("/products/{$product->id}", [
            'nama' => 'New Name',
            'harga' => $product->harga,
        ])->assertOk()->assertJsonPath('resource.nama', 'New Name');
    });

    it('ignores unique sku for same product on update', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['sku' => 'SAME-001']);

        $this->actingAs($admin)->putJson("/products/{$product->id}", [
            'nama' => 'Updated',
            'sku' => 'SAME-001',
            'harga' => 100000,
        ])->assertOk();
    });

    it('ignores unique name for the same product on update', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['nama' => 'Dompet Kulit Klasik']);

        $this->actingAs($admin)->putJson("/products/{$product->id}", [
            'nama' => 'Dompet Kulit Klasik',
            'harga' => 100000,
        ])->assertOk();
    });
});

describe('product destroy', function () {
    it('soft deletes a product', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)->deleteJson("/products/{$product->id}")->assertOk()->assertJsonPath('success', true);

        expect(Product::find($product->id))->toBeNull();
        expect(Product::withTrashed()->find($product->id))->not->toBeNull();
        expect(Product::withTrashed()->find($product->id)->trashed())->toBeTrue();
    });
});

describe('product stock', function () {
    it('restocks and records movement', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 10]);

        $this->actingAs($admin)->postJson("/products/{$product->id}/stock", [
            'aksi' => 'restok',
            'jumlah' => 5,
            'keterangan' => 'Restok bulanan',
        ])->assertOk();

        expect($product->fresh()->stok)->toBe(15);
        expect(StockMovement::where('product_id', $product->id)->where('sumber', 'restok')->exists())->toBeTrue();
    });

    it('corrects stock to absolute value', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 10]);

        $this->actingAs($admin)->postJson("/products/{$product->id}/stock", [
            'aksi' => 'koreksi',
            'stok_baru' => 7,
        ])->assertOk();

        expect($product->fresh()->stok)->toBe(7);
    });

    it('blocks pegawai from adjusting stock', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 10]);

        $this->actingAs($pegawai)->postJson("/products/{$product->id}/stock", [
            'aksi' => 'restok',
            'jumlah' => 1,
        ])->assertForbidden();
    });

    it('allows pegawai to view movements', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create();

        $this->actingAs($pegawai)->getJson("/products/{$product->id}/movements")
            ->assertOk()
            ->assertJsonPath('success', true);
    });

    it('sets initial stock on create via movement', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/products', [
            'nama' => 'Produk Stok',
            'harga' => 100000,
            'stok' => 12,
        ])->assertCreated();

        $product = Product::where('nama', 'Produk Stok')->first();
        expect($product->stok)->toBe(12);
        expect(StockMovement::where('product_id', $product->id)->sum('jumlah'))->toBe(12);
    });
});
