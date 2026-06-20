@extends('layouts.app')

@php
    use App\Enums\ExpertBookingStatus;
@endphp

@section('content')
    <div>
        <x-common.page-breadcrumb pageTitle="Bookings" />

        <div class="mb-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">All expert session bookings made by users.</p>
        </div>

        <x-common.component-card title="All bookings">
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/[0.03]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">ID</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">User</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Expert</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Time</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Meeting channel</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Notes</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Booked</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-white/[0.02]">
                        @forelse ($bookings as $booking)
                            @php
                                $statusClass = match ($booking->status) {
                                    ExpertBookingStatus::Confirmed => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200',
                                    ExpertBookingStatus::Cancelled => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
                                };
                            @endphp
                            <tr>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">{{ $booking->id }}</td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">
                                    <div class="font-medium">{{ $booking->user->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $booking->user->email ?? '' }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">
                                    <div class="font-medium">{{ $booking->expert->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $booking->expert->email ?? '' }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">
                                    {{ $booking->scheduled_date?->format('M j, Y') }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">
                                    {{ $booking->start_time?->format('H:i') }} – {{ $booking->end_time?->format('H:i') }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ $booking->status->value }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm font-mono text-gray-600 dark:text-gray-300">
                                    {{ $booking->agora_channel ?? '—' }}
                                </td>
                                <td class="max-w-xs truncate px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $booking->notes ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $booking->created_at?->format('M j, Y g:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No bookings yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($bookings->hasPages())
                <div class="mt-6">
                    {{ $bookings->links() }}
                </div>
            @endif
        </x-common.component-card>
    </div>
@endsection
