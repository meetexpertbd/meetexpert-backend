<?php

namespace App\Services;

use App\Enums\ExpertBookingStatus;
use App\Models\BookingReview;
use App\Models\ExpertBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BookingReviewService
{
    public function create(User $user, ExpertBooking $booking, array $data): BookingReview
    {
        $this->assertBooker($user, $booking);
        $this->assertReviewable($booking);

        if ($booking->review !== null) {
            throw ValidationException::withMessages([
                'booking' => ['This booking has already been reviewed.'],
            ]);
        }

        return BookingReview::query()->create([
            'expert_booking_id' => $booking->id,
            'user_id' => $user->id,
            'expert_user_id' => $booking->expert_user_id,
            'rating' => (int) $data['rating'],
            'comment' => $data['comment'] ?? null,
        ])->load(['user', 'expert']);
    }

    public function update(User $user, ExpertBooking $booking, array $data): BookingReview
    {
        $this->assertBooker($user, $booking);

        $review = $booking->review;
        if ($review === null) {
            throw ValidationException::withMessages([
                'booking' => ['This booking has not been reviewed yet.'],
            ]);
        }

        $review->update([
            'rating' => (int) $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return $review->fresh(['user', 'expert']);
    }

    private function assertBooker(User $user, ExpertBooking $booking): void
    {
        if ($booking->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'booking' => ['Only the booker can review this booking.'],
            ]);
        }
    }

    private function assertReviewable(ExpertBooking $booking): void
    {
        if ($booking->status !== ExpertBookingStatus::Confirmed) {
            throw ValidationException::withMessages([
                'booking' => ['Only confirmed bookings can be reviewed.'],
            ]);
        }

        $endsAt = Carbon::parse(
            $booking->scheduled_date->toDateString().' '.$booking->end_time->format('H:i:s')
        );

        if ($endsAt->isFuture()) {
            throw ValidationException::withMessages([
                'booking' => ['You can review this booking after the session ends.'],
            ]);
        }
    }
}
