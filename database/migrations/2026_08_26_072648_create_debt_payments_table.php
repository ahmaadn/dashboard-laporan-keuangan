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
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained('debts')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->date('tanggal');
            $table->decimal('nominal', 15, 2)->comment('Nilai pembayaran, selalu positif');
            $table->string('sumber', 20)->default('kas_usaha')->comment('kas_usaha atau dana_pribadi');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['debt_id', 'tanggal']);
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
    }
};
