<?php

namespace App\Models;

use App\Enums\ExpertBookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExpertBooking extends Model
{
    protected $fillable = [
        'user_id',
        'expert_user_id',
        'expert_availability_slot_id',
        'scheduled_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'agora_channel',
        'meeting_joins',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
            'status' => ExpertBookingStatus::class,
            'meeting_joins' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expert_user_id');
    }

    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(ExpertAvailabilitySlot::class, 'expert_availability_slot_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(BookingReview::class);
    }
}
