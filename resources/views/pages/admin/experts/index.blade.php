@extends('layouts.app')

@section('content')
    <div x-data="{
        async confirmDelete(e, name) {
            const form = e.target.closest('form');
            if (!form || !window.Swal) return;
            const { isConfirmed } = await window.Swal.fire({
                title: 'Delete expert?',
                text: name
                    ? '“' + name + '” will be removed permanently.'
                    : 'This expert will be removed permanently.',
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
    }">
        <x-common.page-breadcrumb pageTitle="Experts" />

        <div class="mb-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">Users with the expert role. Open a profile for full user, application, skill link, and availability data.</p>
        </div>

        <x-common.component-card title="All experts">
            <div class="admin-datatable rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="js-datatable min-w-full divide-y divide-gray-200 dark:divide-gray-800"
                    data-bulk-url="{{ route('admin.experts.bulk-destroy') }}"
                    data-bulk-noun="expert" data-bulk-noun-plural="experts">
                    <thead class="bg-gray-50 dark:bg-white/[0.03]">
                        <tr>
                            <x-admin.dt-checkbox all />
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Expert code</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Registration from</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Availability slots</th>
                            <th class="no-sort px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-white/[0.02]">
                        @foreach ($experts as $expert)
                            @php
                                $avatarUrl = $expert->expertDetail?->avatarUrl();
                                $initial = strtoupper(mb_substr(trim($expert->name ?: '?'), 0, 1));
                            @endphp
                            <tr>
                                <x-admin.dt-checkbox :id="$expert->id" :disabled="auth()->user()?->is($expert)" />
                                <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">
                                    <div class="flex items-center gap-3">
                                        @if ($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="{{ $expert->name }}"
                                                class="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                                        @else
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 ring-1 ring-gray-200 dark:bg-white/10 dark:text-gray-300 dark:ring-gray-700"
                                                aria-hidden="true">
                                                {{ $initial }}
                                            </div>
                                        @endif
                                        <span>{{ $expert->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $expert->email }}</td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">{{ $expert->expertDetail?->expert_code ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $expert->registration_from instanceof \BackedEnum ? str_replace('_', ' ', $expert->registration_from->value) : ($expert->registration_from ?? '—') }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">{{ $expert->expert_availability_slots_count }}</td>
                                <td class="px-5 py-4 text-right text-sm">
                                    <a href="{{ route('admin.experts.show', $expert) }}"
                                        class="font-medium text-brand-500 hover:text-brand-600">
                                        View
                                    </a>
                                    @if ($expert->expertDetail)
                                        <a href="{{ route('admin.experts.edit', $expert) }}"
                                            class="ml-3 font-medium text-brand-500 hover:text-brand-600">
                                            Edit
                                        </a>
                                    @endif
                                    <form action="{{ route('admin.experts.destroy', $expert) }}" method="post"
                                        class="ml-3 inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            class="font-medium text-red-600 hover:text-red-700 dark:text-red-400"
                                            @click="confirmDelete($event, @js($expert->name))">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-common.component-card>
    </div>
@endsection
