<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('capital_injections')
            ->whereNotNull('debt_id')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->each(function (object $debtCapital): void {
                DB::table('capital_injections')->insert([
                    'user_id' => $debtCapital->user_id,
                    'tanggal' => $debtCapital->tanggal,
                    'nominal' => $debtCapital->nominal,
                    'debt_id' => null,
                    'keterangan' => $debtCapital->keterangan,
                    'created_at' => $debtCapital->created_at,
                    'updated_at' => $debtCapital->updated_at,
                    'deleted_at' => null,
                ]);
            });
    }

    public function down(): void
    {
        // Data companions represent real cash inflows and are intentionally retained.
    }
};
