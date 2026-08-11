<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpertBooking;
use App\Services\AgoraMeetingService;
use Illuminate\View\View;

class BookingsWebController extends Controller
{
    public function __construct(
        private AgoraMeetingService $agoraMeetingService
    ) {}

    public function index(): View
    {
        $bookings = ExpertBooking::query()
            ->with(['user', 'expert'])
            ->orderByDesc('scheduled_date')
            ->orderBy('start_time')
            ->get();

        return view('pages.admin.bookings.index', [
            'title' => 'Bookings',
            'bookings' => $bookings,
        ]);
    }

    public function show(ExpertBooking $booking): View
    {
        $booking->load([
            'user.profile',
            'expert.expertDetail',
            'expert.expertSlotPrice',
            'availabilitySlot',
        ]);

        $meetingJoins = $this->agoraMeetingService->normalizeMeetingJoins($booking->meeting_joins);
        $window = $this->agoraMeetingService->meetingWindow($booking);

        return view('pages.admin.bookings.show', [
            'title' => 'Booking #'.$booking->id,
            'booking' => $booking,
            'meetingJoins' => $meetingJoins,
            'window' => $window,
        ]);
    }
}
