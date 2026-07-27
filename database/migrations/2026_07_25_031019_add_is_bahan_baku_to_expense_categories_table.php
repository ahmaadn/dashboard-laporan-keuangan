<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->boolean('is_bahan_baku')->default(false)->after('nama')
                ->comment('Kategori biaya produksi; tercermin via HPP, bukan biaya operasional');
        });

        DB::table('expense_categories')->where('nama', 'Bahan Baku')->update(['is_bahan_baku' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('is_bahan_baku');
        });
    }
};
