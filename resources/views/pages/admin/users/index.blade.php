@extends('layouts.app')

@php
    $input =
        'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
    $createHasErrors = $errors->any();
@endphp

@section('content')
    <div x-data="{
        createOpen: @json($createHasErrors && ! request()->routeIs('admin.users.apply-for-expert*')),
        async confirmDelete(e, name) {
            const form = e.target.closest('form');
            if (!form || !window.Swal) return;
            const { isConfirmed } = await window.Swal.fire({
                title: 'Delete user?',
                text: name
                    ? '“' + name + '” will be removed permanently.'
                    : 'This user will be removed permanently.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            });
            if (isConfirmed) form.submit();
        }
    }" @keydown.escape.window="createOpen = false">
        <x-common.page-breadcrumb pageTitle="Users" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Accounts with the standard user role (not experts or admins).</p>
            <button type="button" @click="createOpen = true"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">
                Add user
            </button>
        </div>

        <x-common.component-card title="Users">
            <div class="admin-datatable rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="js-datatable min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/[0.03]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Registration from</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email verified</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Joined</th>
                            <th class="no-sort px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-white/[0.02]">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $user->registration_from instanceof \BackedEnum ? str_replace('_', ' ', $user->registration_from->value) : ($user->registration_from ?? '—') }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $user->email_verified_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $user->created_at?->format('M j, Y') }}</td>
                                <td class="px-5 py-4 text-right text-sm">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-3">
                                        @if (! $user->expert_applications_exists)
                                            <a href="{{ route('admin.users.apply-for-expert', $user) }}"
                                                class="font-medium text-brand-500 hover:text-brand-600">
                                                Apply for expert
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400 dark:text-gray-500">Application exists</span>
                                        @endif
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="post" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                class="font-medium text-red-600 hover:text-red-700 dark:text-red-400"
                                                @click="confirmDelete($event, @js($user->name))">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-common.component-card>

        <div x-show="createOpen" x-cloak
            class="fixed inset-0 z-[999999] flex items-center justify-center overflow-y-auto p-5">
            <div @click="createOpen = false"
                class="fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
            <div @click.stop
                class="relative w-full max-w-lg rounded-3xl bg-white p-6 dark:bg-gray-900 sm:p-10"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">
                <button type="button" @click="createOpen = false"
                    class="absolute right-3 top-3 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:right-6 sm:top-6 sm:h-11 sm:w-11">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"
                            fill="currentColor" />
                    </svg>
                </button>
                <h3 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Add user</h3>
                <form action="{{ route('admin.users.store') }}" method="post" class="space-y-5">
                    @csrf

                    <div>
                        <label for="create_name" class="{{ $label }}">Name <span class="text-red-500">*</span></label>
                        <input id="create_name" name="name" type="text" value="{{ old('name') }}" required
                            class="{{ $input }} @error('name') border-red-500 @enderror" />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="create_email" class="{{ $label }}">Email <span class="text-red-500">*</span></label>
                        <input id="create_email" name="email" type="email" value="{{ old('email') }}" required
                            class="{{ $input }} @error('email') border-red-500 @enderror" />
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="create_password" class="{{ $label }}">Password <span class="text-red-500">*</span></label>
                        <input id="create_password" name="password" type="password" required
                            class="{{ $input }} @error('password') border-red-500 @enderror" />
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="create_password_confirmation" class="{{ $label }}">Confirm password <span
                                class="text-red-500">*</span></label>
                        <input id="create_password_confirmation" name="password_confirmation" type="password" required
                            class="{{ $input }}" />
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">
                            Save
                        </button>
                        <button type="button" @click="createOpen = false"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
