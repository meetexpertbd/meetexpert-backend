@extends('layouts.app')

@section('content')
    <div>
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
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Applications</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Availability slots</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-white/[0.02]">
                        @forelse ($experts as $expert)
                            <tr>
                                <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ $expert->name }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $expert->email }}</td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">{{ $expert->expert_applications_count }}</td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">{{ $expert->expert_availability_slots_count }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.experts.show', $expert) }}"
                                        class="text-sm font-medium text-brand-500 hover:text-brand-600">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
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
