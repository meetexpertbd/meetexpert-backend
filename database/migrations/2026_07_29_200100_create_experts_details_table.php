<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experts_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('expert_application_id')->nullable()->constrained('expert_applications')->nullOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->constrained()->restrictOnDelete();
            $table->string('expert_code', 32)->unique();
            $table->string('slug')->unique();
            $table->string('status', 32)->default('active');
            $table->string('professional_headline');
            $table->text('bio');
            $table->unsignedSmallInteger('years_of_experience')->nullable();
            $table->string('registration_value')->nullable();
            $table->string('intro_video')->nullable();
            $table->json('languages')->nullable();
            $table->string('avatar')->nullable();
            $table->json('documents')->nullable();
            $table->json('education')->nullable();
            $table->json('experience')->nullable();
            $table->json('portfolio')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index('status');
        });

        Schema::create('expert_detail_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_detail_id')->constrained('experts_details')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['expert_detail_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_detail_skill');
        Schema::dropIfExists('experts_details');
    }
};
