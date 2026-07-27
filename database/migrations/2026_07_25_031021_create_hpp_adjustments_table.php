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
        Schema::create('hpp_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Admin pencatat koreksi');
            $table->date('tanggal');
            $table->decimal('nominal', 15, 2)->comment('Bertanda: koreksi HPP manual per tanggal');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete; NULL = data aktif');

            $table->index('tanggal');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hpp_adjustments');
    }
};
