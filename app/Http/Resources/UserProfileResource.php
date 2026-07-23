<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'gender' => $this->gender instanceof \BackedEnum ? $this->gender->value : $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'phone' => $this->phone,
            'avatar_url' => $this->avatarUrl(),
            'present_address' => $this->present_address,
            'permanent_address' => $this->permanent_address,
            'district' => $this->district,
            'country' => $this->country,
            'preferred_language' => $this->preferred_language,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
