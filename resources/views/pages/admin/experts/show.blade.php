@extends('layouts.app')

@php
    use App\Enums\ExpertApplicationStatus;

    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    $statusBadge = fn (ExpertApplicationStatus $s) => match ($s) {
        ExpertApplicationStatus::Pending => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        ExpertApplicationStatus::NeedsCorrection => 'bg-amber-100 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200',
        ExpertApplicationStatus::Approved => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200',
        ExpertApplicationStatus::Rejected => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
    };
@endphp

@section('content')
    <div>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-common.page-breadcrumb pageTitle="Expert: {{ $expert->name }}" />
            <a href="{{ route('admin.experts.index') }}" class="text-sm font-medium text-brand-500 hover:text-brand-600">
                ← Back to experts
            </a>
        </div>

        <x-common.component-card title="User">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $expert->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $expert->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Account type</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $expert->user_type }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email verified</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        {{ $expert->email_verified_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </div>
            </dl>
        </x-common.component-card>

        @foreach ($expert->expertApplications as $application)
            <div class="mt-6">
                <x-common.component-card
                    :title="$expert->expertApplications->count() > 1 ? 'Expert application ('.$loop->iteration.' of '.$expert->expertApplications->count().')' : 'Expert application'">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge($application->status) }}">
                                    {{ str_replace('_', ' ', $application->status->value) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reviewed at</dt>
                            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                                {{ $application->reviewed_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Category</dt>
                            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $application->category->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Subcategory</dt>
                            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $application->subcategory->name ?? '—' }}</dd>
                        </div>
                        @if ($application->reviewedBy)
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reviewed by</dt>
                                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $application->reviewedBy->name }}</dd>
                            </div>
                        @endif
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Professional headline</dt>
                            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90 whitespace-pre-wrap">{{ $application->professional_headline }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bio</dt>
                            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90 whitespace-pre-wrap">{{ $application->bio }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Years of experience</dt>
                            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $application->years_of_experience ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Registration value</dt>
                            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $application->registration_value ?? '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Languages</dt>
                            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                                {{ ! empty($application->languages) ? implode(', ', $application->languages) : '—' }}
                            </dd>
                        </div>
                        @if ($application->admin_feedback)
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Admin feedback</dt>
                                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90 whitespace-pre-wrap">{{ $application->admin_feedback }}</dd>
                            </div>
                        @endif
                    </dl>

                    @php
                        $avatarUrl = $application->avatarUrl();
                        $introVideoUrl = $application->introVideoUrl();
                        $documents = $application->documentsWithUrls();
                        $isDirectIntroVideo = $introVideoUrl && preg_match('/\.(mp4|webm|mov|m4v|avi)(\?|$)/i', $introVideoUrl);
                    @endphp

                    <div class="mt-6 border-t border-gray-100 pt-6 dark:border-gray-800">
                        <h3 class="mb-4 text-sm font-medium text-gray-900 dark:text-white/90">Media & documents</h3>
                        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                            <div>
                                <h4 class="mb-3 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Avatar</h4>
                                @if ($avatarUrl)
                                    <div class="flex flex-col gap-3">
                                        <a href="{{ $avatarUrl }}" target="_blank" rel="noopener" class="inline-block w-fit">
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
                                <h4 class="mb-3 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Intro video</h4>
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

                        <div class="mt-6">
                            <h4 class="mb-4 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Documents</h4>
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
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-6 dark:border-gray-800">
                        <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white/90">Education</h3>
                        @if ($application->education === null || $application->education === [])
                            <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
                        @else
                            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($application->education as $row)
                                    @if (is_array($row))
                                        <li class="py-4 first:pt-0 last:pb-0">
                                            <p class="font-medium text-gray-900 dark:text-white/90">{{ $row['institution'] ?? '—' }}</p>
                                            @if (! empty($row['degree']) || ! empty($row['year']))
                                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                                    @if (! empty($row['degree']))
                                                        <span>{{ $row['degree'] }}</span>
                                                    @endif
                                                    @if (! empty($row['degree']) && ! empty($row['year']))
                                                        <span class="text-gray-400 dark:text-gray-500"> · </span>
                                                    @endif
                                                    @if (! empty($row['year']))
                                                        <span>{{ $row['year'] }}</span>
                                                    @endif
                                                </p>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-6 dark:border-gray-800">
                        <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white/90">Experience</h3>
                        @if ($application->experience === null || $application->experience === [])
                            <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
                        @else
                            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($application->experience as $row)
                                    @if (is_array($row))
                                        <li class="py-4 first:pt-0 last:pb-0">
                                            <p class="font-medium text-gray-900 dark:text-white/90">{{ $row['title'] ?? '—' }}</p>
                                            @if (! empty($row['organization']))
                                                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{{ $row['organization'] }}</p>
                                            @endif
                                            @if (! empty($row['start_year']) || ! empty($row['end_year']))
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    @if (! empty($row['start_year']))
                                                        {{ $row['start_year'] }}
                                                    @endif
                                                    @if (! empty($row['start_year']) || ! empty($row['end_year']))
                                                        –
                                                    @endif
                                                    @if (! empty($row['end_year']))
                                                        {{ $row['end_year'] }}
                                                    @elseif (! empty($row['start_year']))
                                                        Present
                                                    @endif
                                                </p>
                                            @endif
                                            @if (! empty($row['description']))
                                                <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">{!! nl2br(e($row['description'])) !!}</div>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-6 dark:border-gray-800">
                        <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white/90">Portfolio</h3>
                        @if ($application->portfolio === null || $application->portfolio === [])
                            <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
                        @else
                            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($application->portfolio as $row)
                                    @if (is_array($row) && ! empty($row['url']))
                                        <li class="py-4 first:pt-0 last:pb-0">
                                            @if (! empty($row['title']))
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
                    </div>
                </x-common.component-card>
            </div>

            <div class="mt-4">
                <x-common.component-card title="Skills">
                    @if ($application->skills->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">No skills linked.</p>
                    @else
                        <ul class="list-inside list-disc space-y-1 text-sm text-gray-800 dark:text-white/90">
                            @foreach ($application->skills as $skill)
                                <li>{{ $skill->name }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-common.component-card>
            </div>
        @endforeach

        @if ($expert->expertApplications->isEmpty())
            <div class="mt-6">
                <x-common.component-card title="Expert applications">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No application rows for this user.</p>
                </x-common.component-card>
            </div>
        @endif

        <div class="mt-6">
            <x-common.component-card title="Availability">
                @if ($expert->expertAvailabilitySlots->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No availability slots.</p>
                @else
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-white/[0.03]">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Day</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">From</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">To</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($expert->expertAvailabilitySlots as $slot)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-800 dark:text-white/90">{{ $dayNames[$slot->day_of_week] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-800 dark:text-white/90">{{ $slot->start_time->format('g:i A') }}</td>
                                        <td class="px-4 py-2 text-gray-800 dark:text-white/90">{{ $slot->end_time->format('g:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-common.component-card>
        </div>
    </div>
@endsection
