<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experts_details', function (Blueprint $table) {
            $table->decimal('slot_price', 10, 2)->nullable()->after('registration_value');
        });
    }

    public function down(): void
    {
        Schema::table('experts_details', function (Blueprint $table) {
            $table->dropColumn('slot_price');
        });
    }
};
