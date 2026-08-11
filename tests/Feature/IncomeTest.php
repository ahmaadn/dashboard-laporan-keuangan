<?php

use App\Http\Resources\IncomeResource;
use App\Models\Income;
use App\Models\Product;
use App\Models\User;
use App\Support\AppTimezone;

describe('income store', function () {
    it('creates an income with computed total', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['harga' => 100000, 'harga_grosir' => 90000, 'stok' => 50, 'harga_modal' => 40000]);

        $response = $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 2,
            'harga_satuan' => 100000,
            'keterangan' => 'Pelanggan tetap',
        ]);

        $response->assertCreated();
        $income = Income::first();
        expect((float) $income->total)->toBe(200000.0);
        expect($income->user_id)->toBe($pegawai->id);
        expect((float) $income->hpp_satuan)->toBe(40000.0);
        expect($product->fresh()->stok)->toBe(48);
    });

    it('applies grosir price for offline qty >= min', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create([
            'harga' => 100000,
            'harga_grosir' => 90000,
            'min_qty_grosir' => 3,
            'stok' => 20,
            'harga_modal' => 40000,
        ]);

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 3,
            'harga_satuan' => 1,
        ])->assertCreated();

        $income = Income::first();
        expect((float) $income->harga_satuan)->toBe(90000.0);
        expect($income->harga_tipe)->toBe('grosir');
        expect((float) $income->total)->toBe(270000.0);
    });

    it('keeps eceran for online even with large qty', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create([
            'harga' => 100000,
            'harga_grosir' => 90000,
            'min_qty_grosir' => 3,
            'stok' => 20,
        ]);

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'online',
            'jumlah' => 5,
            'harga_satuan' => 1,
        ])->assertCreated();

        $income = Income::first();
        expect((float) $income->harga_satuan)->toBe(100000.0);
        expect($income->harga_tipe)->toBe('eceran');
    });

    it('respects manual price override', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['harga' => 100000, 'stok' => 10]);

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 1,
            'harga_satuan' => 75000,
            'harga_manual' => true,
        ])->assertCreated();

        expect((float) Income::first()->harga_satuan)->toBe(75000.0);
        expect(Income::first()->harga_tipe)->toBe('manual');
    });

    it('rejects qty above stock', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 2]);

        $response = $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 5,
            'harga_satuan' => 100000,
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.jumlah'))->not->toBeEmpty();
    });

    it('restores stock on delete', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 10, 'harga' => 50000]);

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 3,
            'harga_satuan' => 50000,
        ])->assertCreated();

        $income = Income::first();
        expect($product->fresh()->stok)->toBe(7);

        $this->actingAs($pegawai)->deleteJson("/income/{$income->id}")->assertOk();
        expect($product->fresh()->stok)->toBe(10);
    });

    it('allows income without product', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => null,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 1,
            'harga_satuan' => 50000,
            'harga_manual' => true,
        ])->assertCreated();
    });

    it('blocks future dates', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => AppTimezone::today()->addDays(2)->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 1,
            'harga_satuan' => 100000,
        ])->assertStatus(422);
    });

    it('validates jumlah minimum', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 0,
            'harga_satuan' => 100000,
        ])->assertStatus(422);
    });

    it('blocks dates before the business start year', function () {
        $pegawai = User::factory()->pegawai()->create();

        $response = $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => '2017-12-31',
            'jenis_transaksi' => 'offline',
            'jumlah' => 1,
            'harga_satuan' => 100000,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('tanggal_transaksi');
        expect($response->json('errors.tanggal_transaksi.0'))
            ->toContain(AppTimezone::TANGGAL_MULAI_USAHA);
    });

    it('accepts the business start date itself', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => AppTimezone::TANGGAL_MULAI_USAHA,
            'jenis_transaksi' => 'offline',
            'jumlah' => 1,
            'harga_satuan' => 100000,
        ])->assertCreated();
    });

    it('requires a valid sales channel', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'marketplace',
            'jumlah' => 1,
            'harga_satuan' => 100000,
        ])->assertStatus(422)->assertJsonValidationErrors('jenis_transaksi');
    });

    it('stores an online sale on the online channel', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['harga' => 100000, 'stok' => 10]);

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'online',
            'jumlah' => 2,
            'harga_satuan' => 100000,
        ])->assertCreated();

        expect(Income::first()->jenis_transaksi->value)->toBe('online');
    });
});

describe('income ownership', function () {
    it('allows owner pegawai to update own transaction', function () {
        $pegawai = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $pegawai->id]);

        $this->actingAs($pegawai)->putJson("/income/{$income->id}", [
            'tanggal_transaksi' => $income->tanggal_transaksi->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 5,
            'harga_satuan' => 200000,
            'harga_manual' => true,
        ])->assertOk();
    });

    it('blocks pegawai from updating others transaction', function () {
        $pegawai = User::factory()->pegawai()->create();
        $other = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $other->id]);

        $this->actingAs($pegawai)->putJson("/income/{$income->id}", [
            'tanggal_transaksi' => $income->tanggal_transaksi->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 5,
            'harga_satuan' => 200000,
            'harga_manual' => true,
        ])->assertForbidden();
    });

    it('allows admin to update any transaction', function () {
        $admin = User::factory()->admin()->create();
        $pegawai = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $pegawai->id]);

        $this->actingAs($admin)->putJson("/income/{$income->id}", [
            'tanggal_transaksi' => $income->tanggal_transaksi->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 2,
            'harga_satuan' => 50000,
            'harga_manual' => true,
        ])->assertOk();
    });

    it('allows owner to delete own transaction', function () {
        $pegawai = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $pegawai->id]);

        $this->actingAs($pegawai)->deleteJson("/income/{$income->id}")->assertOk();
        expect(Income::find($income->id))->toBeNull();
        expect(Income::withTrashed()->find($income->id)->trashed())->toBeTrue();
    });

    it('blocks pegawai from deleting others transaction', function () {
        $pegawai = User::factory()->pegawai()->create();
        $other = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $other->id]);

        $this->actingAs($pegawai)->deleteJson("/income/{$income->id}")->assertForbidden();
    });
});

describe('multi-item cashier store', function () {
    it('creates multiple income rows with shared nomor_transaksi', function () {
        $pegawai = User::factory()->pegawai()->create();
        $p1 = Product::factory()->create(['harga' => 100000, 'stok' => 10, 'harga_modal' => 40000, 'harga_grosir' => null]);
        $p2 = Product::factory()->create(['harga' => 50000, 'stok' => 20, 'harga_modal' => 20000, 'harga_grosir' => null]);

        $response = $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'keterangan' => 'Pelanggan tetap',
            'items' => [
                ['id_produk' => $p1->id, 'jumlah' => 2, 'harga_satuan' => 100000, 'harga_manual' => true],
                ['id_produk' => $p2->id, 'jumlah' => 3, 'harga_satuan' => 50000, 'harga_manual' => true],
            ],
        ]);

        $response->assertCreated();
        expect($response->json('nomor_transaksi'))->toStartWith('TRX-');
        $rows = Income::orderBy('id')->get();
        expect($rows)->toHaveCount(2);
        expect($rows->first()->nomor_transaksi)->toBe($rows->last()->nomor_transaksi);
        expect($rows->first()->nomor_transaksi)->toBe($response->json('nomor_transaksi'));
        expect((float) $rows[0]->total)->toBe(200000.0);
        expect((float) $rows[1]->total)->toBe(150000.0);
        expect($p1->fresh()->stok)->toBe(8);
        expect($p2->fresh()->stok)->toBe(17);
    });

    it('rolls back all rows if any product has insufficient stock', function () {
        $pegawai = User::factory()->pegawai()->create();
        $p1 = Product::factory()->create(['stok' => 10]);
        $p2 = Product::factory()->create(['stok' => 1]);

        $response = $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'items' => [
                ['id_produk' => $p1->id, 'jumlah' => 2, 'harga_satuan' => 100000, 'harga_manual' => true],
                ['id_produk' => $p2->id, 'jumlah' => 5, 'harga_satuan' => 100000, 'harga_manual' => true],
            ],
        ]);

        $response->assertStatus(422);
        expect(Income::count())->toBe(0);
        expect($p1->fresh()->stok)->toBe(10);
        expect($p2->fresh()->stok)->toBe(1);
    });

    it('applies grosir per line item independently', function () {
        $pegawai = User::factory()->pegawai()->create();
        $p1 = Product::factory()->create([
            'harga' => 100000, 'harga_grosir' => 90000, 'min_qty_grosir' => 3, 'stok' => 20,
        ]);
        $p2 = Product::factory()->create([
            'harga' => 80000, 'harga_grosir' => 70000, 'min_qty_grosir' => 5, 'stok' => 20,
        ]);

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'items' => [
                ['id_produk' => $p1->id, 'jumlah' => 5, 'harga_satuan' => 0],
                ['id_produk' => $p2->id, 'jumlah' => 2, 'harga_satuan' => 0],
            ],
        ])->assertCreated();

        $rows = Income::orderBy('id')->get();
        $rowP1 = $rows->firstWhere('product_id', $p1->id);
        $rowP2 = $rows->firstWhere('product_id', $p2->id);
        expect($rowP1->harga_tipe)->toBe('grosir');
        expect((float) $rowP1->harga_satuan)->toBe(90000.0);
        expect($rowP2->harga_tipe)->toBe('eceran');
        expect((float) $rowP2->harga_satuan)->toBe(80000.0);
    });

    it('rejects empty items array', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'items' => [],
        ])->assertStatus(422);
    });

    it('increments nomor_transaksi across same day', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 100, 'harga_grosir' => null]);

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'items' => [
                ['id_produk' => $product->id, 'jumlah' => 1, 'harga_satuan' => 50000, 'harga_manual' => true],
            ],
        ])->assertCreated();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'items' => [
                ['id_produk' => $product->id, 'jumlah' => 1, 'harga_satuan' => 50000, 'harga_manual' => true],
            ],
        ])->assertCreated();

        $nums = Income::orderBy('id')->pluck('nomor_transaksi')->unique()->values();
        expect($nums)->toHaveCount(2);
        expect($nums[1])->not->toBe($nums[0]);
    });
});

describe('nomor_transaksi field', function () {
    it('exposes nomor_transaksi on the resource', function () {
        $income = Income::factory()->create(['nomor_transaksi' => 'TRX-TEST-0001']);
        $payload = (new IncomeResource($income))->resolve();

        expect($payload['nomor_transaksi'])->toBe('TRX-TEST-0001');
    });
});
