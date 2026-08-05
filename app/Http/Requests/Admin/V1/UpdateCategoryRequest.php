<?php

namespace App\Http\Requests\Admin\V1;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code_prefix')) {
            $this->merge([
                'code_prefix' => strtoupper((string) $this->input('code_prefix')),
            ]);
        }
    }

    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'code_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/', Rule::unique('categories', 'code_prefix')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
