@extends('layouts.app')

@php
    use App\Enums\ExpertBookingStatus;
    use Carbon\Carbon;

    $statusClass = match ($booking->status) {
        ExpertBookingStatus::Confirmed => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200',
        ExpertBookingStatus::Cancelled => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
    };

    $formatJoinAt = function (?string $value): string {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            return Carbon::parse($value)->timezone(config('app.timezone'))->format('M j, Y g:i A');
        } catch (\Throwable) {
            return $value;
        }
    };
@endphp

@section('content')
    <div>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-common.page-breadcrumb pageTitle="Booking #{{ $booking->id }}" />
            <a href="{{ route('admin.bookings.index') }}"
                class="text-sm font-medium text-brand-500 hover:text-brand-600">
                ← Back to bookings
            </a>
        </div>

        <x-common.component-card title="Booking overview">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                            {{ $booking->status->value }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Booked at</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        {{ $booking->created_at?->format('M j, Y g:i A') ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Scheduled date</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        {{ $booking->scheduled_date?->format('M j, Y') ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Time</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        {{ $booking->start_time?->format('g:i A') }} – {{ $booking->end_time?->format('g:i A') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Availability slot ID</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        {{ $booking->expert_availability_slot_id ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Meeting channel</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800 dark:text-white/90">
                        {{ $booking->agora_channel ?? '—' }}
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Notes</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90 whitespace-pre-wrap">
                        {{ $booking->notes ?: '—' }}
                    </dd>
                </div>
            </dl>
        </x-common.component-card>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-common.component-card title="User">
                <dl class="grid gap-4">
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $booking->user->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $booking->user->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                            {{ $booking->user?->profile?->phone ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </x-common.component-card>

            <x-common.component-card title="Expert">
                <dl class="grid gap-4">
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $booking->expert->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $booking->expert->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Expert code</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                            {{ $booking->expert?->expertDetail?->expert_code ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Slot price</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                            {{ $booking->expert?->expertSlotPrice?->price !== null
                                ? number_format((float) $booking->expert->expertSlotPrice->price, 2)
                                : '—' }}
                        </dd>
                    </div>
                    @if ($booking->expert?->expertDetail)
                        <div>
                            <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Headline</dt>
                            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                                {{ $booking->expert->expertDetail->professional_headline ?: '—' }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-common.component-card>
        </div>

        <div class="mt-6">
            <x-common.component-card title="Meeting window">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Starts at</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                            {{ $window['starts_at']->format('M j, Y g:i A') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ends at</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                            {{ $window['ends_at']->format('M j, Y g:i A') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Join opens at</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                            {{ $window['join_opens_at']->format('M j, Y g:i A') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Join closes at</dt>
                        <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                            {{ $window['join_closes_at']->format('M j, Y g:i A') }}
                        </dd>
                    </div>
                </dl>
            </x-common.component-card>
        </div>

        <div class="mt-6">
            <x-common.component-card title="Meeting joins">
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-white/[0.03]">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Participant</th>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Joined at</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach (['user' => 'User', 'expert' => 'Expert'] as $role => $label)
                                @php
                                    $join = $meetingJoins[$role] ?? ['status' => 'not_joined', 'joined_at' => null];
                                    $joined = ($join['status'] ?? 'not_joined') === 'joined';
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-gray-800 dark:text-white/90">{{ $label }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $joined
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200'
                                                : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                            {{ $joined ? 'joined' : 'not joined' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-800 dark:text-white/90">
                                        {{ $formatJoinAt($join['joined_at'] ?? null) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-common.component-card>
        </div>
    </div>
@endsection
