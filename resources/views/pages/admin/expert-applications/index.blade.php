@extends('layouts.app')

@php
    use App\Enums\ExpertApplicationStatus;
@endphp

@section('content')
    <div>
        <x-common.page-breadcrumb pageTitle="Expert applications" />

        <div class="mb-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">Review submissions from users applying to become experts.</p>
        </div>

        <x-common.component-card title="All applications">
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/[0.03]">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                ID
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Applicant
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Headline
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Category
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Status
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Submitted
                            </th>
                            <th
                                class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-white/[0.02]">
                        @forelse ($applications as $application)
                            @php
                                $statusClass = match ($application->status) {
                                    ExpertApplicationStatus::Pending => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                                    ExpertApplicationStatus::NeedsCorrection => 'bg-amber-100 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200',
                                    ExpertApplicationStatus::Approved => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200',
                                    ExpertApplicationStatus::Rejected => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
                                };
                            @endphp
                            <tr>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">
                                    {{ $application->id }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">
                                    <div class="font-medium">{{ $application->user->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $application->user->email ?? '' }}
                                    </div>
                                </td>
                                <td class="max-w-xs truncate px-5 py-4 text-sm text-gray-800 dark:text-white/90">
                                    {{ $application->professional_headline }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">
                                    <div>{{ $application->category->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $application->subcategory->name ?? '' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ str_replace('_', ' ', $application->status->value) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $application->created_at?->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.expert-applications.show', $application) }}"
                                        class="text-sm font-medium text-brand-500 hover:text-brand-600">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No applications yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($applications->hasPages())
                <div class="mt-6">
                    {{ $applications->links() }}
                </div>
            @endif
        </x-common.component-card>
    </div>
@endsection
