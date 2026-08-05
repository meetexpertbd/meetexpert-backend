<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->whereNotNull('code_prefix')
            ->orderBy('id')
            ->each(function ($row): void {
                DB::table('categories')
                    ->where('id', $row->id)
                    ->update(['code_prefix' => strtoupper((string) $row->code_prefix)]);
            });

        DB::table('experts_details')
            ->whereNotNull('expert_code')
            ->orderBy('id')
            ->each(function ($row): void {
                DB::table('experts_details')
                    ->where('id', $row->id)
                    ->update(['expert_code' => strtoupper((string) $row->expert_code)]);
            });
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};
