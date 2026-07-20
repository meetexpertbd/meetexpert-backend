@extends('layouts.app')

@section('content')
    <div>
        <x-common.page-breadcrumb pageTitle="All types of users" />

        <div class="mb-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">All accounts from the users table, including admins, experts, and standard users.</p>
        </div>

        <x-common.component-card title="All types of users">
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/[0.03]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Type</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email verified</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Joined</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-white/[0.02]">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                                <td class="px-5 py-4 text-sm capitalize text-gray-600 dark:text-gray-300">{{ $user->user_type }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $user->email_verified_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $user->created_at?->format('M j, Y') }}</td>
                                <td class="px-5 py-4 text-sm">
                                    @if ($user->user_type !== \App\Models\User::USER_TYPE_ADMIN)
                                        <form method="POST" action="{{ route('admin.all-users.make-admin', $user) }}">
                                            @csrf
                                            <x-ui.button type="submit" size="sm" variant="primary" className="!px-3 !py-2">
                                                Make as admin
                                            </x-ui.button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Already admin</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="mt-6">
                    {{ $users->links() }}
                </div>
            @endif
        </x-common.component-card>
    </div>
@endsection
