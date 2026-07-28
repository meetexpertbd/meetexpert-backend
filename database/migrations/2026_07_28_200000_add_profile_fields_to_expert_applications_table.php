<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expert_applications', function (Blueprint $table) {
            $table->unsignedSmallInteger('years_of_experience')->nullable()->after('bio');
            $table->string('registration_value')->nullable()->after('years_of_experience');
            $table->string('intro_video')->nullable()->after('registration_value');
            $table->json('languages')->nullable()->after('intro_video');
        });
    }

    public function down(): void
    {
        Schema::table('expert_applications', function (Blueprint $table) {
            $table->dropColumn([
                'years_of_experience',
                'registration_value',
                'intro_video',
                'languages',
            ]);
        });
    }
};
