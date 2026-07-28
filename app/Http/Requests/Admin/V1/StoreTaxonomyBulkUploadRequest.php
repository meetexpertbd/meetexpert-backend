<?php

namespace App\Http\Requests\Admin\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaxonomyBulkUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'json' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $decoded = json_decode((string) $this->input('json'), true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                $validator->errors()->add('json', 'Invalid JSON.');

                return;
            }

            if (! isset($decoded['categories']) || ! is_array($decoded['categories']) || $decoded['categories'] === []) {
                $validator->errors()->add('json', 'JSON must include a non-empty "categories" array.');

                return;
            }

            foreach ($decoded['categories'] as $categoryIndex => $category) {
                if (! is_array($category) || ! isset($category['name']) || trim((string) $category['name']) === '') {
                    $validator->errors()->add('json', "categories[{$categoryIndex}].name is required.");
                    continue;
                }

                $subcategories = $category['subcategories'] ?? [];
                if (! is_array($subcategories)) {
                    $validator->errors()->add('json', "categories[{$categoryIndex}].subcategories must be an array.");
                    continue;
                }

                foreach ($subcategories as $subIndex => $subcategory) {
                    if (! is_array($subcategory) || ! isset($subcategory['name']) || trim((string) $subcategory['name']) === '') {
                        $validator->errors()->add('json', "categories[{$categoryIndex}].subcategories[{$subIndex}].name is required.");
                        continue;
                    }

                    $skills = $subcategory['skills'] ?? [];
                    if (! is_array($skills)) {
                        $validator->errors()->add('json', "categories[{$categoryIndex}].subcategories[{$subIndex}].skills must be an array.");
                        continue;
                    }

                    foreach ($skills as $skillIndex => $skill) {
                        if (! is_string($skill) && ! is_numeric($skill)) {
                            $validator->errors()->add(
                                'json',
                                "categories[{$categoryIndex}].subcategories[{$subIndex}].skills[{$skillIndex}] must be a string."
                            );
                        }
                    }
                }
            }

            $this->merge(['payload' => $decoded]);
        });
    }

    /**
     * @return array{categories: list<array{name: string, subcategories?: list<array{name: string, skills?: list<string>}>}>}
     */
    public function payload(): array
    {
        return $this->input('payload');
    }
}
