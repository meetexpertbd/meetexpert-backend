<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsersWebController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->where('user_type', User::USER_TYPE_USER)
            ->orderBy('name')
            ->paginate(25);

        return view('pages.admin.users.index', [
            'title' => 'Users',
            'users' => $users,
        ]);
    }

    public function allTypes(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(25);

        return view('pages.admin.users.all-types', [
            'title' => 'All types users',
            'users' => $users,
        ]);
    }

    public function makeAdmin(User $user): RedirectResponse
    {
        if ($user->user_type === User::USER_TYPE_ADMIN) {
            return redirect()
                ->route('admin.all-users.index')
                ->with('info', $user->name.' is already an admin.');
        }

        $user->update(['user_type' => User::USER_TYPE_ADMIN]);

        return redirect()
            ->route('admin.all-users.index')
            ->with('success', $user->name.' is now an admin.');
    }
}
