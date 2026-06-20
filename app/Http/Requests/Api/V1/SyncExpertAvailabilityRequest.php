<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncExpertAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->user_type === User::USER_TYPE_EXPERT;
    }

    protected function prepareForValidation(): void
    {
        $days = $this->input('days');
        if (! is_array($days)) {
            return;
        }
        foreach ($days as $i => $day) {
            if (! is_array($day)) {
                continue;
            }
            if (! array_key_exists('slots', $day) || $day['slots'] === null) {
                $days[$i]['slots'] = [];
            }
        }
        $this->merge(['days' => $days]);
    }

    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'size:7'],
            'days.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'days.*.enabled' => ['required', 'boolean'],
            'days.*.slots' => ['nullable', 'array', 'max:20'],
            'days.*.slots.*.start' => ['required', 'date_format:H:i'],
            'days.*.slots.*.end' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $days = $this->input('days');
            if (! is_array($days)) {
                return;
            }

            $seen = [];
            foreach ($days as $i => $day) {
                if (! is_array($day)) {
                    continue;
                }
                $dow = $day['day_of_week'] ?? null;
                if ($dow !== null) {
                    if (isset($seen[$dow])) {
                        $validator->errors()->add("days.$i.day_of_week", 'Each day_of_week must appear exactly once.');

                        return;
                    }
                    $seen[$dow] = true;
                }
            }
            if (count($seen) !== 7) {
                $validator->errors()->add('days', 'You must provide exactly one entry for each day_of_week from 0 through 6.');

                return;
            }

            foreach ($days as $i => $day) {
                if (! is_array($day)) {
                    continue;
                }
                $slots = $day['slots'] ?? [];
                if (! is_array($slots)) {
                    $slots = [];
                }

                $ranges = [];
                foreach ($slots as $j => $slot) {
                    if (! is_array($slot)) {
                        continue;
                    }
                    $start = $slot['start'] ?? null;
                    $end = $slot['end'] ?? null;
                    if ($start === null || $end === null) {
                        continue;
                    }
                    $startSec = $this->timeToSeconds((string) $start);
                    $endSec = $this->timeToSeconds((string) $end);
                    if ($endSec <= $startSec) {
                        $validator->errors()->add("days.$i.slots.$j.end", 'End time must be after start time.');

                        return;
                    }
                    $ranges[] = [$startSec, $endSec];
                }
                usort($ranges, fn (array $a, array $b): int => $a[0] <=> $b[0]);
                for ($k = 1, $kMax = count($ranges); $k < $kMax; $k++) {
                    if ($ranges[$k][0] < $ranges[$k - 1][1]) {
                        $validator->errors()->add("days.$i.slots", 'Time slots on the same day must not overlap.');

                        return;
                    }
                }
            }
        });
    }

    private function timeToSeconds(string $hi): int
    {
        $parts = explode(':', $hi);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);

        return $h * 3600 + $m * 60;
    }
}
