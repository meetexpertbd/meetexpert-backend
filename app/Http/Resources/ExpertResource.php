<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detail = $this->expertDetail;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'uuid' => $detail?->uuid,
            'expert_code' => $detail?->expert_code,
            'slug' => $detail?->slug,
            'professional_headline' => $detail?->professional_headline,
            'bio' => $detail?->bio,
            'years_of_experience' => $detail?->years_of_experience,
            'registration_value' => $detail?->registration_value,
            'intro_video' => $detail?->intro_video,
            'intro_video_url' => $detail?->introVideoUrl(),
            'languages' => $detail?->languages ?? [],
            'avatar' => $detail?->avatar,
            'avatar_url' => $detail?->avatarUrl(),
            'documents' => $detail?->documentsWithUrls() ?? [],
            'education' => $detail?->education,
            'experience' => $detail?->experience,
            'portfolio' => $detail?->portfolio,
            'category' => $detail?->category ? [
                'id' => $detail->category->id,
                'name' => $detail->category->name,
                'slug' => $detail->category->slug,
            ] : null,
            'subcategory' => $detail?->subcategory ? [
                'id' => $detail->subcategory->id,
                'name' => $detail->subcategory->name,
                'slug' => $detail->subcategory->slug,
            ] : null,
            'skills' => $detail?->skills
                ?->map(fn ($skill) => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'slug' => $skill->slug,
                ])
                ->values()
                ->all() ?? [],
        ];
    }
}
