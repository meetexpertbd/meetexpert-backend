<?php

namespace App\Http\Requests\Admin\V1;

use App\Models\Skill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSkillsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subcategory_id' => ['required', 'integer', 'exists:subcategories,id'],
            'names' => ['required', 'array', 'min:1', 'max:100'],
            'names.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->failed()) {
                return;
            }

            $subcategoryId = (int) $this->input('subcategory_id');
            $names = $this->input('names', []);
            $seen = [];

            foreach ($names as $i => $name) {
                $trimmed = trim($name);
                if ($trimmed === '') {
                    $validator->errors()->add("names.$i", 'Skill name cannot be empty.');

                    continue;
                }
                $key = mb_strtolower($trimmed);
                if (isset($seen[$key])) {
                    $validator->errors()->add("names.$i", 'Duplicate skill in this list.');

                    continue;
                }
                $seen[$key] = true;

                if (Skill::query()->where('subcategory_id', $subcategoryId)->where('name', $trimmed)->exists()) {
                    $validator->errors()->add("names.$i", 'This skill already exists for the selected subcategory.');
                }
            }
        });
    }
}
