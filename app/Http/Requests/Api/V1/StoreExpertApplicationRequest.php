<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpertApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'subcategory_id' => [
                'required',
                'integer',
                Rule::exists('subcategories', 'id')->where('category_id', $this->input('category_id')),
            ],
            'professional_headline' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string', 'max:10000'],
            'education' => ['nullable', 'array', 'max:20'],
            'education.*.institution' => ['required_with:education', 'string', 'max:255'],
            'education.*.degree' => ['nullable', 'string', 'max:255'],
            'education.*.year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'experience' => ['nullable', 'array', 'max:30'],
            'experience.*.title' => ['required_with:experience', 'string', 'max:255'],
            'experience.*.organization' => ['nullable', 'string', 'max:255'],
            'experience.*.start_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'experience.*.end_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'experience.*.description' => ['nullable', 'string', 'max:2000'],
            'portfolio' => ['nullable', 'array', 'max:20'],
            'portfolio.*.title' => ['nullable', 'string', 'max:255'],
            'portfolio.*.url' => ['required', 'url', 'max:2048'],
            'skill_ids' => ['required', 'array', 'min:1', 'max:50'],
            'skill_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('skills', 'id')
                    ->where('subcategory_id', $this->input('subcategory_id'))
                    ->where('is_active', true),
            ],
        ];
    }
}
