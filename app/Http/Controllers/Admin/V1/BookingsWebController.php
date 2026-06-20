<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpertBooking;
use Illuminate\View\View;

class BookingsWebController extends Controller
{
    public function index(): View
    {
        $bookings = ExpertBooking::query()
            ->with(['user', 'expert'])
            ->orderByDesc('scheduled_date')
            ->orderBy('start_time')
            ->paginate(20);

        return view('pages.admin.bookings.index', [
            'title' => 'Bookings',
            'bookings' => $bookings,
        ]);
    }
}
