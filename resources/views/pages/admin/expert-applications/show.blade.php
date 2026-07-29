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
    <div x-data="{
        async confirmDelete(e, name) {
            const form = e.target.closest('form');
            if (!form || !window.Swal) return;
            const { isConfirmed } = await window.Swal.fire({
                title: 'Delete application?',
                text: name
                    ? 'Application for “' + name + '” will be removed permanently.'
                    : 'This application will be removed permanently.',
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
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-common.page-breadcrumb pageTitle="Expert application #{{ $application->id }}" />
            <div class="flex flex-wrap items-center gap-4">
                <form action="{{ route('admin.expert-applications.destroy', $application) }}" method="post">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                        class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400"
                        @click="confirmDelete($event, @js($application->user->name ?? 'applicant'))">
                        Delete application
                    </button>
                </form>
                <a href="{{ route('admin.expert-applications.index') }}"
                    class="text-sm font-medium text-brand-500 hover:text-brand-600">
                    ← Back to list
                </a>
            </div>
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

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-common.component-card title="Languages">
                @if (empty($application->languages))
                    <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
                @else
                    <p class="text-sm text-gray-800 dark:text-white/90">{{ implode(', ', $application->languages) }}</p>
                @endif
            </x-common.component-card>
            <x-common.component-card title="Registration / experience">
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Years of experience</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $application->years_of_experience ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Registration value</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $application->registration_value ?? '—' }}</dd>
                    </div>
                </dl>
            </x-common.component-card>
        </div>

        @php
            $avatarUrl = $application->avatarUrl();
            $introVideoUrl = $application->introVideoUrl();
            $documents = $application->documentsWithUrls();
            $isDirectIntroVideo = $introVideoUrl && preg_match('/\.(mp4|webm|mov|m4v|avi)(\?|$)/i', $introVideoUrl);
        @endphp

        <div class="mt-6">
            <x-common.component-card title="Media & documents">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <div>
                        <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Avatar</h4>
                        @if ($avatarUrl)
                            <div class="flex flex-col gap-3">
                                <a href="{{ $avatarUrl }}" target="_blank" rel="noopener"
                                    class="inline-block w-fit">
                                    <img src="{{ $avatarUrl }}" alt="Expert avatar"
                                        class="h-40 w-40 rounded-xl object-cover border border-gray-200 shadow-sm dark:border-gray-800">
                                </a>
                                <a href="{{ $avatarUrl }}" target="_blank" rel="noopener"
                                    class="text-sm font-medium text-brand-500 hover:text-brand-600">Open image</a>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No avatar uploaded.</p>
                        @endif
                    </div>

                    <div class="xl:col-span-2">
                        <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Intro video</h4>
                        @if ($introVideoUrl)
                            <div class="space-y-3">
                                @if ($isDirectIntroVideo)
                                    <video controls preload="metadata"
                                        class="h-48 w-auto max-w-md rounded-xl border border-gray-200 bg-black object-contain dark:border-gray-800"
                                        src="{{ $introVideoUrl }}">
                                        Your browser does not support the video tag.
                                    </video>
                                @else
                                    <div
                                        class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                        <p class="text-sm text-gray-600 dark:text-gray-300">External video link</p>
                                        <a href="{{ $introVideoUrl }}" target="_blank" rel="noopener"
                                            class="mt-1 block break-all text-sm font-medium text-brand-500 hover:text-brand-600">
                                            {{ $introVideoUrl }}
                                        </a>
                                    </div>
                                @endif
                                <a href="{{ $introVideoUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex text-sm font-medium text-brand-500 hover:text-brand-600">
                                    Open in new tab
                                </a>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No intro video provided.</p>
                        @endif
                    </div>
                </div>

                <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-800">
                    <h4 class="mb-4 text-sm font-medium text-gray-700 dark:text-gray-300">Documents</h4>
                    @if ($documents === [])
                        <p class="text-sm text-gray-500 dark:text-gray-400">No documents uploaded.</p>
                    @else
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($documents as $document)
                                @php
                                    $path = (string) ($document['path'] ?? '');
                                    $url = $document['url'] ?? null;
                                    $name = $document['name'] ?: 'Document';
                                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                    $isPdf = $ext === 'pdf';
                                @endphp
                                <div
                                    class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.02]">
                                    <div class="bg-gray-50 p-3 dark:bg-white/[0.03]">
                                        @if ($url && $isImage)
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="block">
                                                <img src="{{ $url }}" alt="{{ $name }}"
                                                    class="mx-auto h-40 w-full rounded-lg object-contain">
                                            </a>
                                        @elseif ($url && $isPdf)
                                            <iframe src="{{ $url }}" title="{{ $name }}"
                                                class="h-48 w-full rounded-lg border border-gray-200 dark:border-gray-700"></iframe>
                                        @else
                                            <div
                                                class="flex h-40 items-center justify-center rounded-lg border border-dashed border-gray-200 dark:border-gray-700">
                                                <div class="text-center">
                                                    <p class="text-2xl font-semibold uppercase text-gray-400">
                                                        {{ $ext !== '' ? $ext : 'file' }}</p>
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Preview not available</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="space-y-2 p-4">
                                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white/90"
                                            title="{{ $name }}">{{ $name }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $path }}">
                                            {{ $path !== '' ? $path : '—' }}</p>
                                        @if ($url)
                                            <div class="flex flex-wrap gap-3 pt-1">
                                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                                    class="text-sm font-medium text-brand-500 hover:text-brand-600">View</a>
                                                <a href="{{ $url }}" download
                                                    class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white">
                                                    Download
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
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
