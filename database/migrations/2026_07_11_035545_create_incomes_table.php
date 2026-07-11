<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Pencatat transaksi (admin/pegawai)');
            $table->date('tanggal_transaksi');
            $table->unsignedInteger('jumlah')->default(1)->comment('Kuantitas produk terjual');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('total', 15, 2)->comment('jumlah * harga_satuan');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete; NULL = data aktif');

            $table->index('tanggal_transaksi');
            $table->index('product_id');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
