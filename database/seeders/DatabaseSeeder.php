<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder minimum: hanya master data dan akun demo untuk login.
 * Tidak ada data transaksi/produk dummy (per REVISI_KONSEP_KEUANGAN.md &
 * KEBUTUHAN_SISTEM.md — gunakan data asli dari usaha sebagai acuan uji).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedExpenseCategories();
        $this->seedProductCategories();
        $this->seedUsers();
    }

    private function seedExpenseCategories(): void
    {
        // Persis 4 kategori sesuai KEBUTUHAN_SISTEM.md Bagian F #44.
        // "Pengiriman" tetap dipertahankan.
        $rows = [
            ['Bahan Baku', true],
            ['Operasional', false],
            ['Pemasaran', false],
            ['Pengiriman', false],
        ];

        foreach ($rows as [$nama, $isBahanBaku]) {
            ExpenseCategory::updateOrCreate(
                ['nama' => $nama],
                ['is_bahan_baku' => $isBahanBaku],
            );
        }
    }

    private function seedProductCategories(): void
    {
        foreach (['Dompet', 'Tas', 'Sabuk', 'Aksesoris'] as $nama) {
            ProductCategory::firstOrCreate(['nama' => $nama]);
        }
    }

    /** @return array<string, User> */
    private function seedUsers(): array
    {
        $admin = User::firstOrCreate(
            ['username' => 'busari'],
            [
                'nama' => 'Bu Sari',
                'email' => 'busari@leatherdash.id',
                'password' => Hash::make('demo1234'),
                'peran' => 'admin',
                'dapat_melihat_dashboard' => true,
                'is_active' => true,
            ],
        );

        User::firstOrCreate(
            ['username' => 'dimas'],
            [
                'nama' => 'Dimas Pratama',
                'email' => 'dimas@leatherdash.id',
                'password' => Hash::make('demo1234'),
                'peran' => 'pegawai',
                'dapat_melihat_dashboard' => true,
                'is_active' => true,
            ],
        );

        User::firstOrCreate(
            ['username' => 'rina'],
            [
                'nama' => 'Rina Wati',
                'email' => 'rina@leatherdash.id',
                'password' => Hash::make('demo1234'),
                'peran' => 'pegawai',
                'dapat_melihat_dashboard' => false,
                'is_active' => true,
            ],
        );

        return ['admin' => $admin];
    }
}
