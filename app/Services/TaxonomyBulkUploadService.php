<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Skill;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaxonomyBulkUploadService
{
    /**
     * @param  array{categories: list<array{name: string, subcategories?: list<array{name: string, skills?: list<string>}>}>}  $payload
     * @return array{categories_created: int, subcategories_created: int, skills_created: int, categories_skipped: int, subcategories_skipped: int, skills_skipped: int}
     */
    public function import(array $payload): array
    {
        $stats = [
            'categories_created' => 0,
            'subcategories_created' => 0,
            'skills_created' => 0,
            'categories_skipped' => 0,
            'subcategories_skipped' => 0,
            'skills_skipped' => 0,
        ];

        DB::transaction(function () use ($payload, &$stats): void {
            foreach ($payload['categories'] as $categoryData) {
                $categoryName = trim((string) $categoryData['name']);
                $category = $this->findCategoryByName($categoryName);

                if ($category === null) {
                    $category = Category::query()->create([
                        'name' => $categoryName,
                        'slug' => $this->uniqueCategorySlug($categoryName),
                        'description' => null,
                        'sort_order' => 0,
                        'is_active' => true,
                    ]);
                    $stats['categories_created']++;
                } else {
                    $stats['categories_skipped']++;
                }

                foreach ($categoryData['subcategories'] ?? [] as $subcategoryData) {
                    $subcategoryName = trim((string) $subcategoryData['name']);
                    $subcategory = $this->findSubcategoryByName($category->id, $subcategoryName);

                    if ($subcategory === null) {
                        $subcategory = Subcategory::query()->create([
                            'category_id' => $category->id,
                            'name' => $subcategoryName,
                            'slug' => $this->uniqueSubcategorySlug($category->id, $subcategoryName),
                            'description' => null,
                            'sort_order' => 0,
                            'is_active' => true,
                        ]);
                        $stats['subcategories_created']++;
                    } else {
                        $stats['subcategories_skipped']++;
                    }

                    $skillNames = [];
                    foreach ($subcategoryData['skills'] ?? [] as $skillName) {
                        $skillName = trim((string) $skillName);
                        if ($skillName === '') {
                            continue;
                        }
                        $key = mb_strtolower($skillName);
                        if (! isset($skillNames[$key])) {
                            $skillNames[$key] = $skillName;
                        }
                    }

                    foreach ($skillNames as $skillName) {
                        if ($this->findSkillByName($subcategory->id, $skillName) !== null) {
                            $stats['skills_skipped']++;
                            continue;
                        }

                        Skill::query()->create([
                            'subcategory_id' => $subcategory->id,
                            'name' => $skillName,
                            'slug' => $this->uniqueSkillSlug($subcategory->id, $skillName),
                            'description' => null,
                            'sort_order' => 0,
                            'is_active' => true,
                        ]);
                        $stats['skills_created']++;
                    }
                }
            }
        });

        return $stats;
    }

    private function findCategoryByName(string $name): ?Category
    {
        return Category::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }

    private function findSubcategoryByName(int $categoryId, string $name): ?Subcategory
    {
        return Subcategory::query()
            ->where('category_id', $categoryId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }

    private function findSkillByName(int $subcategoryId, string $name): ?Skill
    {
        return Skill::query()
            ->where('subcategory_id', $subcategoryId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }

    private function uniqueCategorySlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $candidate = $base;
        $n = 0;

        while (Category::query()->where('slug', $candidate)->exists()) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }

    private function uniqueSubcategorySlug(int $categoryId, string $name): string
    {
        $base = Str::slug($name) ?: 'subcategory';
        $candidate = $base;
        $n = 0;

        while (Subcategory::query()
            ->where('category_id', $categoryId)
            ->where('slug', $candidate)
            ->exists()) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }

    private function uniqueSkillSlug(int $subcategoryId, string $name): string
    {
        $base = Str::slug($name) ?: 'skill';
        $candidate = $base;
        $n = 0;

        while (Skill::query()
            ->where('subcategory_id', $subcategoryId)
            ->where('slug', $candidate)
            ->exists()) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }
}
