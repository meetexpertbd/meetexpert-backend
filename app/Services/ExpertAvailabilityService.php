<?php

namespace App\Services;

use App\Models\ExpertAvailabilitySlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpertAvailabilityService
{
    /**
     * @return list<array{day_of_week: int, enabled: bool, slots: list<array{start: string, end: string}>}>
     */
    public function getSchedule(User $user): array
    {
        $slotsByDay = ExpertAvailabilitySlot::query()
            ->where('user_id', $user->id)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $days = [];
        for ($d = 0; $d <= 6; $d++) {
            $rows = $slotsByDay->get($d, collect());
            $slots = [];
            foreach ($rows as $row) {
                $slots[] = [
                    'start' => $row->start_time->format('H:i'),
                    'end' => $row->end_time->format('H:i'),
                ];
            }
            $days[] = [
                'day_of_week' => $d,
                'enabled' => $slots !== [],
                'slots' => $slots,
            ];
        }

        return $days;
    }

    /**
     * @param  list<array{day_of_week: int, enabled: bool, slots: list<array{start: string, end: string}>}>  $days
     */
    public function syncSchedule(User $user, array $days): void
    {
        DB::transaction(function () use ($user, $days): void {
            $remainingWanted = [];
            foreach ($days as $day) {
                $dow = (int) ($day['day_of_week'] ?? -1);
                if ($dow < 0 || $dow > 6) {
                    continue;
                }
                if (! ($day['enabled'] ?? false)) {
                    continue;
                }
                foreach ($day['slots'] ?? [] as $slot) {
                    if (! is_array($slot) || ! isset($slot['start'], $slot['end'])) {
                        continue;
                    }
                    $sig = $this->slotSignature($dow, (string) $slot['start'], (string) $slot['end']);
                    $remainingWanted[$sig] = ($remainingWanted[$sig] ?? 0) + 1;
                }
            }

            $existing = ExpertAvailabilitySlot::query()
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get();

            foreach ($existing as $row) {
                $sig = $this->slotSignature(
                    (int) $row->day_of_week,
                    $row->start_time->format('H:i'),
                    $row->end_time->format('H:i')
                );
                if (($remainingWanted[$sig] ?? 0) > 0) {
                    $remainingWanted[$sig]--;
                } else {
                    $row->delete();
                }
            }

            foreach ($remainingWanted as $sig => $count) {
                if ($count < 1) {
                    continue;
                }
                [$dow, $start, $end] = $this->parseSlotSignature($sig);
                for ($i = 0; $i < $count; $i++) {
                    ExpertAvailabilitySlot::query()->create([
                        'user_id' => $user->id,
                        'day_of_week' => $dow,
                        'start_time' => $start,
                        'end_time' => $end,
                    ]);
                }
            }
        });
    }

    private function slotSignature(int $dayOfWeek, string $start, string $end): string
    {
        return json_encode([
            $dayOfWeek,
            $this->normalizeTimeHi($start),
            $this->normalizeTimeHi($end),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{0: int, 1: string, 2: string}
     */
    private function parseSlotSignature(string $signature): array
    {
        $decoded = json_decode($signature, true, 3, JSON_THROW_ON_ERROR);

        return [(int) $decoded[0], (string) $decoded[1], (string) $decoded[2]];
    }

    private function normalizeTimeHi(string $time): string
    {
        return Carbon::parse($time)->format('H:i');
    }
}
