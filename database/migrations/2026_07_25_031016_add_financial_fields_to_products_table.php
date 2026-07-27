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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('harga_modal', 15, 2)->default(0)->after('harga')
                ->comment('HPP satuan; disnapshot ke incomes.hpp_satuan saat penjualan');
            $table->decimal('harga_grosir', 15, 2)->nullable()->after('harga_modal');
            $table->unsignedInteger('min_qty_grosir')->default(3)->after('harga_grosir');
            $table->integer('stok')->default(0)->after('min_qty_grosir');
            $table->unsignedInteger('stok_minimum')->default(5)->after('stok');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['harga_modal', 'harga_grosir', 'min_qty_grosir', 'stok', 'stok_minimum']);
        });
    }
};
