<?php

namespace App\Http\Resources;

use App\Services\AgoraMeetingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $meeting = null;
        $user = $request->user();

        if ($user && ($this->user_id === $user->id || $this->expert_user_id === $user->id)) {
            $meeting = app(AgoraMeetingService::class)->summaryFor($user, $this->resource);
        }

        return [
            'id' => $this->id,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'notes' => $this->notes,
            'expert' => $this->whenLoaded('expert', fn () => [
                'id' => $this->expert->id,
                'name' => $this->expert->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'availability_slot_id' => $this->expert_availability_slot_id,
            'meeting' => $meeting,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
