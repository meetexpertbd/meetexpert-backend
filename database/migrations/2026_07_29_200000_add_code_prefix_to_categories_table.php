<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('code_prefix', 20)->nullable()->after('slug');
        });

        $prefixes = [
            'doctor' => 'Dr',
            'lawyer' => 'Lw',
            'study-abroad-expert' => 'Sa',
        ];

        foreach ($prefixes as $slug => $prefix) {
            DB::table('categories')->where('slug', $slug)->update(['code_prefix' => $prefix]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('code_prefix');
        });
    }
};
