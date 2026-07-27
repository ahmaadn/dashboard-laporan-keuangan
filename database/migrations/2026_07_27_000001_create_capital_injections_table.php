<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capital_injections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Pencatat setoran modal (admin)');
            $table->date('tanggal');
            $table->decimal('nominal', 15, 2)->comment('Suntikan modal pemilik; bukan pendapatan usaha');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tanggal');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capital_injections');
    }
};
