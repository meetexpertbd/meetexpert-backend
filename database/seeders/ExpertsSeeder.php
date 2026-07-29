<?php

namespace Database\Seeders;

use App\Enums\ExpertDetailStatus;
use App\Enums\RegistrationFrom;
use App\Models\Category;
use App\Models\ExpertDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ExpertsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNotNull('code_prefix')
            ->where('code_prefix', '!=', '')
            ->with([
                'subcategories' => fn ($q) => $q->where('is_active', true)->with([
                    'skills' => fn ($sq) => $sq->where('is_active', true),
                ]),
            ])
            ->get()
            ->filter(fn (Category $category) => $category->subcategories
                ->contains(fn ($sub) => $sub->skills->isNotEmpty()))
            ->values();

        if ($categories->isEmpty()) {
            $this->command?->error('No active categories with code_prefix, subcategories, and skills found. Seed taxonomy first.');

            return;
        }

        $languages = ['English', 'Bengali', 'Hindi', 'Arabic'];
        $password = Hash::make('password');

        for ($i = 1; $i <= 20; $i++) {
            $category = $categories->random();
            $subcategory = $category->subcategories
                ->filter(fn ($sub) => $sub->skills->isNotEmpty())
                ->random();
            $skills = $subcategory->skills->random(min(3, $subcategory->skills->count()));

            $name = fake()->name();
            $email = "expert{$i}@meetexpert.test";

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password,
                    'user_type' => User::USER_TYPE_EXPERT,
                    'registration_from' => RegistrationFrom::AdminPanel,
                    'email_verified_at' => now(),
                ]
            );

            if ($user->expertDetail()->exists()) {
                continue;
            }

            $prefix = rtrim((string) $category->code_prefix, '-');
            $expertCode = $this->uniqueExpertCode($prefix);
            $slug = $this->uniqueSlug($category, $user, $expertCode);

            $detail = ExpertDetail::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'expert_application_id' => null,
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'expert_code' => $expertCode,
                'slug' => $slug,
                'status' => ExpertDetailStatus::Active,
                'professional_headline' => fake()->sentence(6),
                'bio' => fake()->paragraphs(2, true),
                'years_of_experience' => fake()->numberBetween(1, 25),
                'registration_value' => strtoupper(fake()->bothify('REG-####')),
                'intro_video' => null,
                'languages' => fake()->randomElements($languages, fake()->numberBetween(1, 3)),
                'avatar' => null,
                'documents' => null,
                'education' => [
                    [
                        'institution' => fake()->company().' University',
                        'degree' => fake()->randomElement(['BSc', 'MSc', 'MBBS', 'LLB', 'MBA']),
                        'year' => fake()->numberBetween(2005, 2022),
                    ],
                ],
                'experience' => [
                    [
                        'title' => fake()->jobTitle(),
                        'organization' => fake()->company(),
                        'start_year' => fake()->numberBetween(2010, 2018),
                        'end_year' => fake()->numberBetween(2019, 2025),
                        'description' => fake()->sentence(),
                    ],
                ],
                'portfolio' => [
                    [
                        'title' => fake()->words(3, true),
                        'url' => fake()->url(),
                    ],
                ],
            ]);

            $detail->skills()->sync($skills->pluck('id')->all());
        }

        $this->command?->info('Seeded 20 experts (expert1@meetexpert.test … expert20@meetexpert.test / password).');
    }

    private function uniqueExpertCode(string $prefix): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $code = $prefix.'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (! ExpertDetail::query()->where('expert_code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.'-'.Str::upper(Str::random(4));
    }

    private function uniqueSlug(Category $category, User $user, string $expertCode): string
    {
        $base = Str::slug(implode('-', array_filter([
            $category->slug ?: $category->name,
            $user->name,
            $expertCode,
        ]))) ?: 'expert-'.$expertCode;

        $candidate = $base;
        $n = 0;

        while (ExpertDetail::query()->where('slug', $candidate)->exists()) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }
}
