<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'user_type' => $this->user_type,
            'registration_from' => $this->registration_from instanceof \BackedEnum
                ? $this->registration_from->value
                : $this->registration_from,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'profile' => $this->when(
                $this->relationLoaded('profile'),
                fn () => $this->profile
                    ? new UserProfileResource($this->profile)
                    : null
            ),
            'expert_profile' => $this->when(
                $this->user_type === User::USER_TYPE_EXPERT && $this->relationLoaded('expertDetail'),
                fn () => $this->expertDetail
                    ? new ExpertResource($this->resource)
                    : null
            ),
        ];
    }
}
