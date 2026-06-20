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

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'subcategory_id' => [
                'sometimes',
                'integer',
                Rule::exists('subcategories', 'id')->where(function ($query): void {
                    if ($this->filled('category_id')) {
                        $query->where('category_id', $this->input('category_id'));
                    }
                }),
            ],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
