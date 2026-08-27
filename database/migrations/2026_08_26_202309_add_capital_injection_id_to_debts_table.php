<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->foreignId('capital_injection_id')->nullable()->unique()->after('user_id')->constrained('capital_injections')->nullOnDelete()->cascadeOnUpdate();
        });

        DB::table('debts')
            ->whereNull('capital_injection_id')
            ->orderBy('id')
            ->each(function (object $debt): void {
                $memo = DB::table('capital_injections')->where('debt_id', $debt->id)->first();

                if (! $memo) {
                    return;
                }

                $capital = DB::table('capital_injections')
                    ->whereNull('debt_id')
                    ->where('user_id', $memo->user_id)
                    ->where('tanggal', $memo->tanggal)
                    ->where('nominal', $memo->nominal)
                    ->where('created_at', $memo->created_at)
                    ->orderBy('id')
                    ->first();

                if ($capital) {
                    DB::table('debts')->where('id', $debt->id)->update(['capital_injection_id' => $capital->id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropForeign(['capital_injection_id']);
            $table->dropUnique(['capital_injection_id']);
            $table->dropColumn('capital_injection_id');
        });
    }
};
