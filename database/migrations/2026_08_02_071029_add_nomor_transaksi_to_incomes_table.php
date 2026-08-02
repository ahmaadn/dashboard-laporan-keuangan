<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->string('nomor_transaksi', 32)->nullable()->after('id')
                ->comment('Nomor struk; baris dengan nomor sama = satu transaksi kasir');
            $table->index('nomor_transaksi');
        });

        foreach (DB::table('incomes')->orderBy('id')->get(['id']) as $row) {
            DB::table('incomes')->where('id', $row->id)->update([
                'nomor_transaksi' => 'TRX-LEGACY-'.$row->id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex(['nomor_transaksi']);
            $table->dropColumn('nomor_transaksi');
        });
    }
};
