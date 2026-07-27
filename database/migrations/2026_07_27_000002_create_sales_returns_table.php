<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_id')
                ->constrained('incomes')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Penjualan asal yang diretur');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->cascadeOnUpdate()
                ->comment('Snapshot produk; nullable bila produk dihapus');
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Pencatat retur (admin/pegawai)');
            $table->date('tanggal');
            $table->unsignedInteger('jumlah')->comment('Unit barang yang diretur');
            $table->decimal('nominal_retur', 15, 2)
                ->comment('Nilai retur = jumlah × harga_satuan saat transaksi penjualan');
            $table->string('alasan', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tanggal');
            $table->index('income_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
