<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListExpertsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $skillIds = $this->input('skill_ids', []);

        if (! is_array($skillIds)) {
            $skillIds = [$skillIds];
        }

        if ($this->filled('skill_id')) {
            $skillIds[] = $this->input('skill_id');
        }

        $skillIds = collect($skillIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'skill_ids' => $skillIds === [] ? null : $skillIds,
        ]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'subcategory_id' => [
                'sometimes',
                'integer',
                Rule::exists('subcategories', 'id')->where(function ($query): void {
                    if ($this->filled('category_id')) {
                        $query->where('category_id', $this->integer('category_id'));
                    }
                }),
            ],
            'skill_id' => ['sometimes', 'integer', $this->skillExistsRule()],
            'skill_ids' => ['sometimes', 'nullable', 'array', 'max:50'],
            'skill_ids.*' => ['integer', 'distinct', $this->skillExistsRule()],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    private function skillExistsRule()
    {
        return Rule::exists('skills', 'id')->where(function ($query): void {
            if ($this->filled('subcategory_id')) {
                $query->where('subcategory_id', $this->integer('subcategory_id'));

                return;
            }

            if ($this->filled('category_id')) {
                $query->whereIn('subcategory_id', function ($subQuery): void {
                    $subQuery->select('id')
                        ->from('subcategories')
                        ->where('category_id', $this->integer('category_id'));
                });
            }
        });
    }
}
