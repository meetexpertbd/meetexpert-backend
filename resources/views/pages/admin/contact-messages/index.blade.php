@extends('layouts.app')

@php
    use App\Enums\ContactMessageStatus;
    use Carbon\Carbon;

    $formatInboxDate = function (?Carbon $date): string {
        if ($date === null) {
            return '';
        }

        if ($date->isToday()) {
            return $date->format('g:i A');
        }

        if ($date->isCurrentYear()) {
            return $date->format('M j');
        }

        return $date->format('M j, Y');
    };

    $folders = [
        'inbox' => ['label' => 'Inbox', 'count' => $counts['inbox']],
        'unread' => ['label' => 'Unread', 'count' => $counts['unread']],
        'read' => ['label' => 'Read', 'count' => $counts['read']],
        'replied' => ['label' => 'Replied', 'count' => $counts['replied']],
    ];
@endphp

@section('content')
    <div>
        <x-common.page-breadcrumb pageTitle="Contact messages" />

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex min-h-[70vh] flex-col lg:flex-row">
                <aside class="border-b border-gray-200 p-3 lg:w-56 lg:border-b-0 lg:border-r dark:border-gray-800">
                    <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Mail</p>
                    <nav class="flex gap-1 overflow-x-auto lg:flex-col">
                        @foreach ($folders as $key => $item)
                            @php
                                $active = $folder === $key;
                            @endphp
                            <a href="{{ route('admin.contact-messages.index', array_filter(['folder' => $key === 'inbox' ? null : $key, 'q' => $search ?: null])) }}"
                                class="flex items-center justify-between gap-3 rounded-full px-4 py-2 text-sm whitespace-nowrap {{ $active ? 'bg-brand-50 font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' }}">
                                <span>{{ $item['label'] }}</span>
                                @if ($item['count'] > 0)
                                    <span class="text-xs {{ $active || $key === 'unread' ? 'font-semibold' : 'text-gray-400' }}">
                                        {{ $item['count'] }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                </aside>

                <div class="min-w-0 flex-1">
                    <form method="get" action="{{ route('admin.contact-messages.index') }}"
                        class="flex items-center gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                        @if ($folder !== 'inbox')
                            <input type="hidden" name="folder" value="{{ $folder }}">
                        @endif
                        <div class="relative flex-1">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-3-3"></path>
                            </svg>
                            <input type="search" name="q" value="{{ $search }}" placeholder="Search mail"
                                class="h-10 w-full rounded-full border-0 bg-gray-100 pl-10 pr-4 text-sm text-gray-800 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:bg-white/5 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:bg-gray-900">
                        </div>
                        <button type="submit"
                            class="rounded-full bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                            Search
                        </button>
                    </form>

                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($messages as $item)
                            @php
                                $unread = $item->status === ContactMessageStatus::Pending;
                                $initial = strtoupper(mb_substr(trim($item->name ?: '?'), 0, 1));
                                $snippet = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $item->message), 70);
                            @endphp
                            <a href="{{ route('admin.contact-messages.show', $item) }}"
                                class="group flex items-center gap-3 px-4 py-3 transition hover:z-10 hover:relative hover:shadow-sm {{ $unread ? 'bg-white dark:bg-transparent' : 'bg-gray-50/80 dark:bg-white/[0.02]' }}">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">
                                    {{ $initial }}
                                </span>
                                <span class="w-36 shrink-0 truncate text-sm {{ $unread ? 'font-bold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-300' }}">
                                    {{ $item->name }}
                                </span>
                                <span class="min-w-0 flex-1 truncate text-sm">
                                    <span class="{{ $unread ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $item->subject }}
                                    </span>
                                    <span class="text-gray-400"> — {{ $snippet }}</span>
                                </span>
                                <span class="hidden shrink-0 text-xs text-gray-400 sm:inline">
                                    @if ($item->status === ContactMessageStatus::Replied)
                                        Replied
                                    @elseif ($unread)
                                        Unread
                                    @endif
                                </span>
                                <span class="w-20 shrink-0 text-right text-xs {{ $unread ? 'font-semibold text-gray-800 dark:text-white' : 'text-gray-500' }}">
                                    {{ $formatInboxDate($item->created_at) }}
                                </span>
                            </a>
                        @empty
                            <div class="px-6 py-16 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if ($search !== '')
                                    No messages match “{{ $search }}”.
                                @else
                                    No messages in this folder.
                                @endif
                            </div>
                        @endforelse
                    </div>

                    @if ($messages->hasPages())
                        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                            {{ $messages->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
