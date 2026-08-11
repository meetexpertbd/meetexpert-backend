<?php

namespace App\Services;

use App\Enums\ExpertBookingStatus;
use App\Models\BookingReview;
use App\Models\ExpertBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ExpertDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function forExpert(User $expert): array
    {
        $expert->loadMissing(['expertDetail', 'expertSlotPrice']);

        $today = now()->toDateString();
        $nowTime = now()->format('H:i:s');
        $slotPrice = $expert->expertSlotPrice?->price !== null
            ? (float) $expert->expertSlotPrice->price
            : null;

        $totalBookings = $this->bookings($expert)->count();
        $cancelledBookings = $this->bookings($expert)
            ->where('status', ExpertBookingStatus::Cancelled)
            ->count();
        $todaysBookings = $this->bookings($expert)
            ->where('status', ExpertBookingStatus::Confirmed)
            ->whereDate('scheduled_date', $today)
            ->count();
        $upcomingBookingsCount = $this->upcomingQuery($expert, $today, $nowTime)->count();
        $completedBookings = $this->completedQuery($expert, $today, $nowTime)->count();

        $reviewStats = BookingReview::query()
            ->where('expert_user_id', $expert->id)
            ->selectRaw('COUNT(*) as total_reviews, AVG(rating) as average_rating')
            ->first();

        $totalReviews = (int) ($reviewStats->total_reviews ?? 0);
        $averageRating = $reviewStats->average_rating !== null
            ? round((float) $reviewStats->average_rating, 1)
            : null;

        $upcoming = $this->upcomingQuery($expert, $today, $nowTime)
            ->with(['user.profile'])
            ->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $recentReviews = BookingReview::query()
            ->where('expert_user_id', $expert->id)
            ->with(['user.profile'])
            ->latest()
            ->limit(5)
            ->get();

        $detail = $expert->expertDetail;

        return [
            'profile' => [
                'id' => $expert->id,
                'name' => $expert->name,
                'slug' => $detail?->slug,
                'expert_code' => $detail?->expert_code,
                'professional_headline' => $detail?->professional_headline,
                'avatar_url' => $detail?->avatarUrl(),
                'status' => $detail?->status instanceof \BackedEnum
                    ? $detail->status->value
                    : $detail?->status,
                'slot_price' => $slotPrice,
            ],
            'stats' => [
                'total_bookings' => $totalBookings,
                'upcoming_bookings' => $upcomingBookingsCount,
                'completed_bookings' => $completedBookings,
                'cancelled_bookings' => $cancelledBookings,
                'todays_bookings' => $todaysBookings,
                'total_reviews' => $totalReviews,
                'average_rating' => $averageRating,
                'estimated_earnings' => $slotPrice !== null
                    ? round($completedBookings * $slotPrice, 2)
                    : null,
            ],
            'upcoming_bookings' => $upcoming,
            'recent_reviews' => $recentReviews,
        ];
    }

    private function bookings(User $expert): Builder
    {
        return ExpertBooking::query()->where('expert_user_id', $expert->id);
    }

    private function upcomingQuery(User $expert, string $today, string $nowTime): Builder
    {
        return $this->bookings($expert)
            ->where('status', ExpertBookingStatus::Confirmed)
            ->where(function (Builder $query) use ($today, $nowTime): void {
                $query->whereDate('scheduled_date', '>', $today)
                    ->orWhere(function (Builder $sameDay) use ($today, $nowTime): void {
                        $sameDay->whereDate('scheduled_date', $today)
                            ->where('end_time', '>=', $nowTime);
                    });
            });
    }

    private function completedQuery(User $expert, string $today, string $nowTime): Builder
    {
        return $this->bookings($expert)
            ->where('status', ExpertBookingStatus::Confirmed)
            ->where(function (Builder $query) use ($today, $nowTime): void {
                $query->whereDate('scheduled_date', '<', $today)
                    ->orWhere(function (Builder $sameDay) use ($today, $nowTime): void {
                        $sameDay->whereDate('scheduled_date', $today)
                            ->where('end_time', '<', $nowTime);
                    });
            });
    }
}
