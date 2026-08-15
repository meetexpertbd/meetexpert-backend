<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\BulkDestroyRequest;
use App\Models\ExpertBooking;
use App\Services\AgoraMeetingService;
use Illuminate\Http\RedirectResponse;
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

    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse
    {
        $count = ExpertBooking::query()->whereIn('id', $request->ids())->delete();

        if ($count === 0) {
            return redirect()
                ->route('admin.bookings.index')
                ->with('danger', 'No bookings were deleted.');
        }

        return redirect()
            ->route('admin.bookings.index')
            ->with('danger', $count === 1 ? 'Booking deleted.' : $count.' bookings deleted.');
    }
}
