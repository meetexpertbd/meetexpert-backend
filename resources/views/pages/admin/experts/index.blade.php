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
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/[0.03]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Expert code</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Registration from</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Availability slots</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-white/[0.02]">
                        @forelse ($experts as $expert)
                            <tr>
                                <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ $expert->name }}</td>
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
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No experts yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($experts->hasPages())
                <div class="mt-6">
                    {{ $experts->links() }}
                </div>
            @endif
        </x-common.component-card>
    </div>
@endsection
