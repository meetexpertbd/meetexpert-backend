@extends('layouts.app')

@php
    use App\Enums\ContactMessageStatus;

    $statusLabel = match ($message->status) {
        ContactMessageStatus::Pending => 'Unread',
        ContactMessageStatus::Read => 'Read',
        ContactMessageStatus::Replied => 'Replied',
        default => $message->status instanceof \BackedEnum ? $message->status->value : $message->status,
    };
    $initial = strtoupper(mb_substr(trim($message->name ?: '?'), 0, 1));
@endphp

@section('content')
    <div x-data="{
        async confirmDelete(e) {
            const form = e.target.closest('form');
            if (!form || !window.Swal) return;
            const { isConfirmed } = await window.Swal.fire({
                title: 'Delete this message?',
                text: 'This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            });
            if (isConfirmed) form.submit();
        }
    }">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.contact-messages.index') }}"
                class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Back to inbox
            </a>
            <div class="flex flex-wrap items-center gap-2">
                @if ($message->status !== ContactMessageStatus::Replied)
                    <form method="post" action="{{ route('admin.contact-messages.mark-replied', $message) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-full bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                            Mark as replied
                        </button>
                    </form>
                @endif
                @if ($message->status !== ContactMessageStatus::Pending)
                    <form method="post" action="{{ route('admin.contact-messages.mark-unread', $message) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Mark as unread
                        </button>
                    </form>
                @endif
                <form method="post" action="{{ route('admin.contact-messages.destroy', $message) }}">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                        class="rounded-full px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                        @click="confirmDelete($event)">
                        Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                <h1 class="text-2xl font-normal text-gray-900 dark:text-white">{{ $message->subject }}</h1>
                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="mb-6 flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-500 text-base font-semibold text-white">
                    {{ $initial }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $message->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if ($message->email)
                                    &lt;{{ $message->email }}&gt;
                                @else
                                    No email provided
                                @endif
                            </p>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $message->created_at?->format('M j, Y, g:i A') }}
                        </p>
                    </div>
                    <dl class="mt-3 grid gap-1 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                        <div>Phone: <span class="text-gray-800 dark:text-white/90">{{ $message->phone }}</span></div>
                        <div>
                            Language:
                            <span class="uppercase text-gray-800 dark:text-white/90">{{ $message->preferred_language ?: '—' }}</span>
                        </div>
                        @if ($message->user)
                            <div class="sm:col-span-2">
                                Account:
                                <span class="text-gray-800 dark:text-white/90">
                                    {{ $message->user->name }} ({{ $message->user->email }}) · {{ $message->user->user_type }}
                                </span>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-6 text-sm leading-7 whitespace-pre-wrap text-gray-800 dark:border-gray-800 dark:text-white/90">
                {{ $message->message }}
            </div>

            @if ($message->email)
                <div class="mt-8 border-t border-gray-100 pt-6 dark:border-gray-800">
                    <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}"
                        class="inline-flex items-center rounded-full bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                        Reply by email
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
