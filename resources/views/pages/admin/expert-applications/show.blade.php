@extends('layouts.app')

@php
    use App\Enums\ExpertApplicationStatus;

    $statusClass = match ($application->status) {
        ExpertApplicationStatus::Pending => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        ExpertApplicationStatus::NeedsCorrection => 'bg-amber-100 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200',
        ExpertApplicationStatus::Approved => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200',
        ExpertApplicationStatus::Rejected => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
    };

    $canReview = in_array($application->status, [ExpertApplicationStatus::Pending, ExpertApplicationStatus::NeedsCorrection], true);

    $input =
        'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 min-h-[120px] w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
@endphp

@section('content')
    <div>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-common.page-breadcrumb pageTitle="Expert application #{{ $application->id }}" />
            <a href="{{ route('admin.expert-applications.index') }}"
                class="text-sm font-medium text-brand-500 hover:text-brand-600">
                ← Back to list
            </a>
        </div>

        <x-common.component-card title="Overview">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="mt-1">
                        <span
                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                            {{ str_replace('_', ' ', $application->status->value) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Submitted</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        {{ $application->created_at?->format('M j, Y g:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Last updated</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        {{ $application->updated_at?->format('M j, Y g:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Applicant</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        <span class="font-medium">{{ $application->user->name ?? '—' }}</span>
                        <span class="block text-gray-500 dark:text-gray-400">{{ $application->user->email ?? '' }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Category</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        {{ $application->category->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Subcategory</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        {{ $application->subcategory->name ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Skills</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        @if ($application->skills->isEmpty())
                            —
                        @else
                            {{ $application->skills->pluck('name')->join(', ') }}
                        @endif
                    </dd>
                </div>
            </dl>
        </x-common.component-card>

        <div class="mt-6">
            <x-common.component-card title="Professional headline">
                <p class="text-sm text-gray-800 dark:text-white/90 whitespace-pre-wrap">
                    {{ $application->professional_headline }}</p>
            </x-common.component-card>
        </div>

        <div class="mt-6">
            <x-common.component-card title="Bio">
                <p class="text-sm text-gray-800 dark:text-white/90 whitespace-pre-wrap">{{ $application->bio }}</p>
            </x-common.component-card>
        </div>

        <div class="mt-6">
            <x-common.component-card title="Education">
                @if ($application->education === null || $application->education === [])
                    <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($application->education as $row)
                            @if (is_array($row))
                                <li class="py-4 first:pt-0 last:pb-0">
                                    <p class="font-medium text-gray-900 dark:text-white/90">
                                        {{ $row['institution'] ?? '—' }}</p>
                                    @if (!empty($row['degree']) || !empty($row['year']))
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            @if (!empty($row['degree']))
                                                <span>{{ $row['degree'] }}</span>
                                            @endif
                                            @if (!empty($row['degree']) && !empty($row['year']))
                                                <span class="text-gray-400 dark:text-gray-500"> · </span>
                                            @endif
                                            @if (!empty($row['year']))
                                                <span>{{ $row['year'] }}</span>
                                            @endif
                                        </p>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </x-common.component-card>
        </div>

        <div class="mt-6">
            <x-common.component-card title="Experience">
                @if ($application->experience === null || $application->experience === [])
                    <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($application->experience as $row)
                            @if (is_array($row))
                                <li class="py-4 first:pt-0 last:pb-0">
                                    <p class="font-medium text-gray-900 dark:text-white/90">
                                        {{ $row['title'] ?? '—' }}</p>
                                    @if (!empty($row['organization']))
                                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $row['organization'] }}</p>
                                    @endif
                                    @if (!empty($row['start_year']) || !empty($row['end_year']))
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            @if (!empty($row['start_year']))
                                                {{ $row['start_year'] }}
                                            @endif
                                            @if (!empty($row['start_year']) || !empty($row['end_year']))
                                                – 
                                            @endif
                                            @if (!empty($row['end_year']))
                                                {{ $row['end_year'] }}
                                            @elseif (!empty($row['start_year']))
                                                Present
                                            @endif
                                        </p>
                                    @endif
                                    @if (!empty($row['description']))
                                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                            {!! nl2br(e($row['description'])) !!}</div>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </x-common.component-card>
        </div>

        <div class="mt-6">
            <x-common.component-card title="Portfolio">
                @if ($application->portfolio === null || $application->portfolio === [])
                    <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($application->portfolio as $row)
                            @if (is_array($row) && !empty($row['url']))
                                <li class="py-4 first:pt-0 last:pb-0">
                                    @if (!empty($row['title']))
                                        <p class="font-medium text-gray-900 dark:text-white/90">{{ $row['title'] }}</p>
                                    @endif
                                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener noreferrer"
                                        class="mt-1 inline-block text-sm text-brand-500 hover:text-brand-600 break-all">
                                        {{ $row['url'] }}</a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </x-common.component-card>
        </div>

        <div class="mt-6">
            <x-common.component-card title="Review">
                @if ($canReview)
                    <form method="post" class="mb-6">
                        @csrf
                        <div>
                            <label for="review-note" class="{{ $label }}">Note to applicant</label>
                            <textarea id="review-note" name="note" rows="4" required
                                class="{{ $input }}">{{ old('note') }}</textarea>
                            @error('note')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="submit"
                                formaction="{{ route('admin.expert-applications.approve', $application) }}"
                                class="inline-flex items-center justify-center rounded-lg bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-700">
                                Approve
                            </button>
                            <button type="submit"
                                formaction="{{ route('admin.expert-applications.reject', $application) }}"
                                class="inline-flex items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700">
                                Reject
                            </button>
                        </div>
                    </form>
                    <div class="mb-6 border-t border-gray-200 dark:border-gray-800"></div>
                @endif
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Admin feedback</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90 whitespace-pre-wrap">
                            {{ $application->admin_feedback ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reviewed at</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                            {{ $application->reviewed_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reviewed by</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                            @if ($application->reviewedBy)
                                <span class="font-medium">{{ $application->reviewedBy->name }}</span>
                                <span
                                    class="block text-gray-500 dark:text-gray-400">{{ $application->reviewedBy->email }}</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-common.component-card>
        </div>
    </div>
@endsection
