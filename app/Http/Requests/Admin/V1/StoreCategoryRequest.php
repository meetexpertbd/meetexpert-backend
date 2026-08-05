<?php

namespace App\Http\Requests\Admin\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
            'code_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/', Rule::unique('categories', 'code_prefix')],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
