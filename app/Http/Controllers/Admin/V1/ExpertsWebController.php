<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpertsWebController extends Controller
{
    public function index(): View
    {
        $experts = User::query()
            ->where('user_type', User::USER_TYPE_EXPERT)
            ->with('expertDetail')
            ->withCount(['expertAvailabilitySlots'])
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
            'expertDetail.category',
            'expertDetail.subcategory',
            'expertDetail.skills',
            'expertAvailabilitySlots' => fn ($q) => $q->orderBy('day_of_week')->orderBy('start_time'),
        ]);

        return view('pages.admin.experts.show', [
            'title' => 'Expert: '.$user->name,
            'expert' => $user,
        ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            abort(404);
        }

        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.experts.index')
                ->with('danger', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.experts.index')
            ->with('danger', $name.' deleted.');
    }
}
