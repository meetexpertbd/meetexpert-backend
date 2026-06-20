<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpertBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->user_type !== User::USER_TYPE_ADMIN;
    }

    public function rules(): array
    {
        return [
            'expert_id' => ['required', 'integer', 'exists:users,id'],
            'availability_slot_id' => ['required', 'integer', 'exists:expert_availability_slots,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
