<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_slot_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        if (Schema::hasColumn('experts_details', 'slot_price')) {
            $rows = DB::table('experts_details')
                ->whereNotNull('slot_price')
                ->get(['user_id', 'slot_price', 'created_at', 'updated_at']);

            foreach ($rows as $row) {
                DB::table('expert_slot_prices')->insert([
                    'user_id' => $row->user_id,
                    'price' => $row->slot_price,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }

            Schema::table('experts_details', function (Blueprint $table) {
                $table->dropColumn('slot_price');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('experts_details', 'slot_price')) {
            Schema::table('experts_details', function (Blueprint $table) {
                $table->decimal('slot_price', 10, 2)->nullable()->after('registration_value');
            });
        }

        if (Schema::hasTable('expert_slot_prices')) {
            $rows = DB::table('expert_slot_prices')->get(['user_id', 'price']);
            foreach ($rows as $row) {
                DB::table('experts_details')
                    ->where('user_id', $row->user_id)
                    ->update(['slot_price' => $row->price]);
            }

            Schema::dropIfExists('expert_slot_prices');
        }
    }
};
