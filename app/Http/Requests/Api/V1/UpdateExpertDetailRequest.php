<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpertDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->user_type === User::USER_TYPE_EXPERT;
    }

    protected function prepareForValidation(): void
    {
        foreach (['languages', 'education', 'experience', 'portfolio', 'skill_ids'] as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$key => $decoded]);
                }
            }
        }
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
            'years_of_experience' => ['required', 'integer', 'min:0', 'max:80'],
            'registration_value' => ['required', 'string', 'max:255'],
            'intro_video' => $this->hasFile('intro_video')
                ? ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo', 'max:51200']
                : ['nullable', 'string', 'url', 'max:2048'],
            'languages' => ['required', 'array', 'min:1', 'max:20'],
            'languages.*' => ['required', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*.name' => ['required', 'string', 'max:255'],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
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
