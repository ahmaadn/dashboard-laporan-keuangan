<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capital_injections', function (Blueprint $table) {
            $table->foreignId('debt_id')->nullable()->unique()->after('nominal')->constrained('debts')->nullOnDelete()->cascadeOnUpdate();
        });

        DB::table('capital_injections')
            ->where('nominal', '<', 0)
            ->orderBy('id')
            ->each(function (object $capital): void {
                $debtId = DB::table('debts')->insertGetId([
                    'user_id' => $capital->user_id,
                    'tanggal' => $capital->tanggal,
                    'nominal' => abs((float) $capital->nominal),
                    'keterangan' => $capital->keterangan,
                    'created_at' => $capital->created_at,
                    'updated_at' => $capital->updated_at,
                ]);

                DB::table('capital_injections')->where('id', $capital->id)->update([
                    'nominal' => abs((float) $capital->nominal),
                    'debt_id' => $debtId,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('capital_injections', function (Blueprint $table) {
            $table->dropForeign(['debt_id']);
            $table->dropUnique(['debt_id']);
            $table->dropColumn('debt_id');
        });
    }
};
