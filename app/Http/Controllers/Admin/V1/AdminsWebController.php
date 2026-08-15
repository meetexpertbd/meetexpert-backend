<?php

namespace App\Http\Controllers\Admin\V1;

use App\Enums\RegistrationFrom;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\BulkDestroyRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminsWebController extends Controller
{
    public function index(): View
    {
        $admins = User::query()
            ->where('user_type', User::USER_TYPE_ADMIN)
            ->orderBy('name')
            ->get();

        return view('pages.admin.admins.index', [
            'title' => 'Admins',
            'admins' => $admins,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'user_type' => User::USER_TYPE_ADMIN,
            'registration_from' => RegistrationFrom::AdminPanel,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Admin created.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->user_type !== User::USER_TYPE_ADMIN) {
            return redirect()
                ->route('admin.admins.index')
                ->with('danger', 'Only admin accounts can be deleted from this list.');
        }

        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.admins.index')
                ->with('danger', 'You cannot delete your own account.');
        }

        if ($this->adminCount() <= 1) {
            return redirect()
                ->route('admin.admins.index')
                ->with('danger', 'At least one admin must remain.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.admins.index')
            ->with('danger', $name.' deleted.');
    }

    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse
    {
        $admins = User::query()
            ->whereIn('id', $request->ids())
            ->where('user_type', User::USER_TYPE_ADMIN)
            ->whereKeyNot($request->user()->id)
            ->get();

        $remaining = $this->adminCount() - $admins->count();
        if ($remaining < 1) {
            return redirect()
                ->route('admin.admins.index')
                ->with('danger', 'At least one admin must remain.');
        }

        $count = $admins->count();
        $admins->each->delete();

        if ($count === 0) {
            return redirect()
                ->route('admin.admins.index')
                ->with('danger', 'No admins were deleted.');
        }

        return redirect()
            ->route('admin.admins.index')
            ->with('danger', $count === 1 ? 'Admin deleted.' : $count.' admins deleted.');
    }

    private function adminCount(): int
    {
        return User::query()->where('user_type', User::USER_TYPE_ADMIN)->count();
    }
}
