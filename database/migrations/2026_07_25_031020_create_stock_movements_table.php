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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Pencatat mutasi (admin/pegawai)');
            $table->date('tanggal');
            $table->string('jenis', 10)->comment('masuk | keluar | koreksi');
            $table->integer('jumlah')->comment('Bertanda: + menambah stok, - mengurangi stok');
            $table->string('sumber', 15)->comment('penjualan | restok | koreksi');
            $table->unsignedBigInteger('ref_id')->nullable()->comment('ID income terkait (jika sumber penjualan)');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
