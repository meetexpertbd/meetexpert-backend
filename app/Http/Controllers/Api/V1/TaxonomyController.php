<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class TaxonomyController extends Controller
{
    #[OA\Get(
        path: '/api/v1/taxonomy',
        tags: ['Taxonomy'],
        summary: 'Get categories with nested subcategories and skills',
        responses: [
            new OA\Response(response: 200, description: 'Taxonomy retrieved'),
        ]
    )]
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with([
                'subcategories' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with([
                        'skills' => fn ($query) => $query
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                    ]),
            ])
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'subcategories' => $category->subcategories->map(fn ($subcategory) => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                    'description' => $subcategory->description,
                    'skills' => $subcategory->skills->map(fn ($skill) => [
                        'id' => $skill->id,
                        'name' => $skill->name,
                        'slug' => $skill->slug,
                        'description' => $skill->description,
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return ApiResponse::success('Taxonomy retrieved.', [
            'categories' => $categories,
        ]);
    }
}
