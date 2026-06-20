<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->constrained()->restrictOnDelete();
            $table->string('status', 32)->default('pending');
            $table->string('professional_headline');
            $table->text('bio');
            $table->json('education')->nullable();
            $table->json('experience')->nullable();
            $table->json('portfolio')->nullable();
            $table->text('admin_feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('expert_application_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['expert_application_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_application_skill');
        Schema::dropIfExists('expert_applications');
    }
};
