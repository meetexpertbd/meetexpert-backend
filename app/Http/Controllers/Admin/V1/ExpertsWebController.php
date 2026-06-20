<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class ExpertsWebController extends Controller
{
    public function index(): View
    {
        $experts = User::query()
            ->where('user_type', User::USER_TYPE_EXPERT)
            ->withCount(['expertApplications', 'expertAvailabilitySlots'])
            ->orderBy('name')
            ->paginate(20);

        return view('pages.admin.experts.index', [
            'title' => 'Experts',
            'experts' => $experts,
        ]);
    }

    public function show(User $user): View
    {
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            abort(404);
        }

        $user->load([
            'expertApplications' => fn ($q) => $q->orderByDesc('id'),
            'expertApplications.category',
            'expertApplications.subcategory',
            'expertApplications.skills',
            'expertApplications.reviewedBy',
            'expertAvailabilitySlots' => fn ($q) => $q->orderBy('day_of_week')->orderBy('start_time'),
        ]);

        return view('pages.admin.experts.show', [
            'title' => 'Expert: '.$user->name,
            'expert' => $user,
        ]);
    }
}
