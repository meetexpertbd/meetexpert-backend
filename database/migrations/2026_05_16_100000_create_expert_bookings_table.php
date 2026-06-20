<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expert_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('expert_availability_slot_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 32);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['expert_user_id', 'scheduled_date']);
            $table->index(['user_id', 'scheduled_date']);
            $table->index(['expert_availability_slot_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_bookings');
    }
};
