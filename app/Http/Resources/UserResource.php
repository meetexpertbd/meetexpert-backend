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
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'expert_profile' => $this->when(
                $this->user_type === User::USER_TYPE_EXPERT && $this->relationLoaded('approvedExpertApplication'),
                fn () => $this->approvedExpertApplication
                    ? new ExpertApplicationResource($this->approvedExpertApplication)
                    : null
            ),
        ];
    }
}
