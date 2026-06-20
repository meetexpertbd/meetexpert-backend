<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
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
}
