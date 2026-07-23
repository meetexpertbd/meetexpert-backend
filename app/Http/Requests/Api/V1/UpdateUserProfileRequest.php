<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\UserGender;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->user_type === User::USER_TYPE_USER;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'gender' => ['sometimes', 'nullable', Rule::enum(UserGender::class)],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:32',
                Rule::unique('user_profiles', 'phone')->ignore($userId, 'user_id'),
            ],
            'avatar' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'present_address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'permanent_address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'district' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'preferred_language' => ['sometimes', 'nullable', 'string', Rule::in(['en', 'bn'])],
        ];
    }
}
