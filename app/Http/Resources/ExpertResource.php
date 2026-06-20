<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $application = $this->approvedExpertApplication;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'professional_headline' => $application?->professional_headline,
            'bio' => $application?->bio,
            'education' => $application?->education,
            'experience' => $application?->experience,
            'portfolio' => $application?->portfolio,
            'category' => $application?->category ? [
                'id' => $application->category->id,
                'name' => $application->category->name,
                'slug' => $application->category->slug,
            ] : null,
            'subcategory' => $application?->subcategory ? [
                'id' => $application->subcategory->id,
                'name' => $application->subcategory->name,
                'slug' => $application->subcategory->slug,
            ] : null,
            'skills' => $application?->skills
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
