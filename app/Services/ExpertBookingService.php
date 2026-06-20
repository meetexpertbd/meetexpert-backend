<?php

namespace App\Services;

use App\Enums\ExpertBookingStatus;
use App\Models\ExpertAvailabilitySlot;
use App\Models\ExpertBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpertBookingService
{
    /**
     * @return Collection<int, array{id: int, day_of_week: int, start: string, end: string, is_booked: bool}>
     */
    public function availableSlotsForDate(User $expert, string $date): Collection
    {
        if ($expert->user_type !== User::USER_TYPE_EXPERT || ! $expert->approvedExpertApplication) {
            throw ValidationException::withMessages([
                'expert' => ['The selected expert is not available.'],
            ]);
        }

        $scheduledDate = Carbon::parse($date)->startOfDay();
        $dayOfWeek = $scheduledDate->dayOfWeek;

        $slots = ExpertAvailabilitySlot::query()
            ->where('user_id', $expert->id)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get();

        $bookedSlotIds = ExpertBooking::query()
            ->where('expert_user_id', $expert->id)
            ->whereDate('scheduled_date', $scheduledDate->toDateString())
            ->where('status', ExpertBookingStatus::Confirmed)
            ->pluck('expert_availability_slot_id')
            ->all();

        $now = Carbon::now();

        return $slots->map(function (ExpertAvailabilitySlot $slot) use ($scheduledDate, $bookedSlotIds, $now): array {
            $startsAt = Carbon::parse(
                $scheduledDate->toDateString().' '.$slot->start_time->format('H:i:s')
            );
            $isBooked = in_array($slot->id, $bookedSlotIds, true) || $startsAt->isPast();

            return [
                'id' => $slot->id,
                'day_of_week' => (int) $slot->day_of_week,
                'start' => $slot->start_time->format('H:i'),
                'end' => $slot->end_time->format('H:i'),
                'is_booked' => $isBooked,
            ];
        })->values();
    }

    public function create(User $booker, array $data): ExpertBooking
    {
        if ($booker->user_type === User::USER_TYPE_ADMIN) {
            throw ValidationException::withMessages([
                'user' => ['Administrator accounts cannot book experts.'],
            ]);
        }

        $expert = User::query()
            ->where('id', (int) $data['expert_id'])
            ->where('user_type', User::USER_TYPE_EXPERT)
            ->whereHas('approvedExpertApplication')
            ->first();

        if ($expert === null) {
            throw ValidationException::withMessages([
                'expert_id' => ['The selected expert is not available.'],
            ]);
        }

        if ($booker->id === $expert->id) {
            throw ValidationException::withMessages([
                'expert_id' => ['You cannot book yourself.'],
            ]);
        }

        $slot = ExpertAvailabilitySlot::query()
            ->where('id', (int) $data['availability_slot_id'])
            ->where('user_id', $expert->id)
            ->first();

        if ($slot === null) {
            throw ValidationException::withMessages([
                'availability_slot_id' => ['The selected slot does not belong to this expert.'],
            ]);
        }

        $scheduledDate = Carbon::parse($data['date'])->startOfDay();

        if ($scheduledDate->dayOfWeek !== (int) $slot->day_of_week) {
            throw ValidationException::withMessages([
                'date' => ['The date does not match the day of week for this availability slot.'],
            ]);
        }

        $slotStart = $slot->start_time->format('H:i:s');
        $slotEnd = $slot->end_time->format('H:i:s');
        $startsAt = Carbon::parse($scheduledDate->toDateString().' '.$slotStart);

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages([
                'date' => ['This slot is no longer available.'],
            ]);
        }

        return DB::transaction(function () use ($booker, $expert, $slot, $scheduledDate, $slotStart, $slotEnd, $data): ExpertBooking {
            $activeConflict = ExpertBooking::query()
                ->where('expert_user_id', $expert->id)
                ->whereDate('scheduled_date', $scheduledDate->toDateString())
                ->where('status', ExpertBookingStatus::Confirmed)
                ->where('start_time', '<', $slotEnd)
                ->where('end_time', '>', $slotStart)
                ->lockForUpdate()
                ->exists();

            if ($activeConflict) {
                throw ValidationException::withMessages([
                    'availability_slot_id' => ['This time slot is already booked.'],
                ]);
            }

            $slotTaken = ExpertBooking::query()
                ->where('expert_availability_slot_id', $slot->id)
                ->whereDate('scheduled_date', $scheduledDate->toDateString())
                ->where('status', ExpertBookingStatus::Confirmed)
                ->lockForUpdate()
                ->exists();

            if ($slotTaken) {
                throw ValidationException::withMessages([
                    'availability_slot_id' => ['This availability slot is already booked for the selected date.'],
                ]);
            }

            $booking = ExpertBooking::query()->create([
                'user_id' => $booker->id,
                'expert_user_id' => $expert->id,
                'expert_availability_slot_id' => $slot->id,
                'scheduled_date' => $scheduledDate->toDateString(),
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'status' => ExpertBookingStatus::Confirmed,
                'notes' => $data['notes'] ?? null,
            ]);

            $booking->update(['agora_channel' => 'booking-'.$booking->id]);

            return $booking->load(['expert', 'availabilitySlot']);
        });
    }

    public function cancel(User $user, ExpertBooking $booking): ExpertBooking
    {
        if ($booking->user_id !== $user->id && $booking->expert_user_id !== $user->id) {
            throw ValidationException::withMessages([
                'booking' => ['You are not allowed to cancel this booking.'],
            ]);
        }

        if ($booking->status === ExpertBookingStatus::Cancelled) {
            throw ValidationException::withMessages([
                'booking' => ['This booking is already cancelled.'],
            ]);
        }

        $startsAt = Carbon::parse(
            $booking->scheduled_date->toDateString().' '.$booking->start_time->format('H:i:s')
        );

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages([
                'booking' => ['Past bookings cannot be cancelled.'],
            ]);
        }

        $booking->update(['status' => ExpertBookingStatus::Cancelled]);

        return $booking->fresh(['expert', 'availabilitySlot']);
    }
}
