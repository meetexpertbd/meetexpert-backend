<?php

use App\Models\ExpertBooking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expert_bookings', function (Blueprint $table) {
            $table->string('agora_channel', 64)->nullable()->unique()->after('notes');
        });

        ExpertBooking::query()
            ->whereNull('agora_channel')
            ->orderBy('id')
            ->each(function (ExpertBooking $booking): void {
                $booking->update(['agora_channel' => 'booking-'.$booking->id]);
            });
    }

    public function down(): void
    {
        Schema::table('expert_bookings', function (Blueprint $table) {
            $table->dropColumn('agora_channel');
        });
    }
};
