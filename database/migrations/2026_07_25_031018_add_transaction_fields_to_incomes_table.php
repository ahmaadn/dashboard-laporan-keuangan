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
        Schema::table('incomes', function (Blueprint $table) {
            $table->string('jenis_transaksi', 10)->default('offline')->after('tanggal_transaksi')
                ->comment('Kanal penjualan: online | offline');
            $table->decimal('hpp_satuan', 15, 2)->default(0)->after('harga_satuan')
                ->comment('Snapshot harga_modal produk saat transaksi');
            $table->string('harga_tipe', 10)->default('manual')->after('hpp_satuan')
                ->comment('Asal harga: eceran | grosir | manual');

            $table->index('jenis_transaksi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex(['jenis_transaksi']);
            $table->dropColumn(['jenis_transaksi', 'hpp_satuan', 'harga_tipe']);
        });
    }
};
