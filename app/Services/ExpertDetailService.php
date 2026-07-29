<?php

namespace App\Services;

use App\Enums\ExpertDetailStatus;
use App\Models\Category;
use App\Models\ExpertApplication;
use App\Models\ExpertDetail;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpertDetailService
{
    public function createFromApprovedApplication(ExpertApplication $application): ExpertDetail
    {
        $application->loadMissing(['user', 'category', 'skills']);

        if (ExpertDetail::query()->where('user_id', $application->user_id)->exists()) {
            throw ValidationException::withMessages([
                'application' => ['Expert details already exist for this user.'],
            ]);
        }

        $category = $application->category;
        $prefix = trim((string) ($category?->code_prefix ?? ''));
        if ($prefix === '') {
            throw ValidationException::withMessages([
                'category_id' => ['Category code prefix is required before approving an expert. Set it on the category first.'],
            ]);
        }

        $expertCode = $this->generateUniqueExpertCode($prefix);
        $slug = $this->generateUniqueSlug($category, $application->user, $expertCode);

        $detail = ExpertDetail::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $application->user_id,
            'expert_application_id' => null,
            'category_id' => $application->category_id,
            'subcategory_id' => $application->subcategory_id,
            'expert_code' => $expertCode,
            'slug' => $slug,
            'status' => ExpertDetailStatus::Active,
            'professional_headline' => $application->professional_headline,
            'bio' => $application->bio,
            'years_of_experience' => $application->years_of_experience,
            'registration_value' => $application->registration_value,
            'intro_video' => $application->intro_video,
            'languages' => $application->languages,
            'avatar' => $application->avatar,
            'documents' => $application->documents,
            'education' => $application->education,
            'experience' => $application->experience,
            'portfolio' => $application->portfolio,
        ]);

        $skillIds = $application->skills->pluck('id')->all();
        if ($skillIds !== []) {
            $detail->skills()->attach($skillIds);
        }

        return $detail->load(['category', 'subcategory', 'skills', 'user']);
    }

    private function generateUniqueExpertCode(string $prefix): string
    {
        $prefix = rtrim($prefix, '-');

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $digits = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $code = $prefix.'-'.$digits;

            if (! ExpertDetail::query()->where('expert_code', $code)->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'expert_code' => ['Unable to generate a unique expert code. Please try again.'],
        ]);
    }

    private function generateUniqueSlug(Category $category, User $user, string $expertCode): string
    {
        $base = Str::slug(implode('-', array_filter([
            $category->slug ?: $category->name,
            $user->name,
            $expertCode,
        ])));

        if ($base === '') {
            $base = 'expert-'.$expertCode;
        }

        $candidate = $base;
        $n = 0;
        while (ExpertDetail::query()->where('slug', $candidate)->exists()) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }
}
