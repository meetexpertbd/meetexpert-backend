<?php

namespace App\Http\Requests\Admin\V1;

use App\Models\Skill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Skill $skill */
        $skill = $this->route('skill');

        return [
            'subcategory_id' => ['required', 'integer', 'exists:subcategories,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('skills', 'name')
                    ->where(fn ($query) => $query->where('subcategory_id', $this->input('subcategory_id')))
                    ->ignore($skill->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
