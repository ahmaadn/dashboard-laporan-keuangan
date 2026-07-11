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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('expense_categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Pencatat transaksi');
            $table->date('tanggal_transaksi');
            $table->decimal('nominal', 15, 2);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete; NULL = data aktif');

            $table->index('tanggal_transaksi');
            $table->index('category_id');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
