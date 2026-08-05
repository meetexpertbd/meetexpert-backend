<?php

namespace App\Services;

use App\Enums\ExpertBookingStatus;
use App\Models\ExpertBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Peterujah\Agora\Agora;
use Peterujah\Agora\Builders\RtcToken;
use Peterujah\Agora\Roles;
use Peterujah\Agora\User as AgoraUser;

class AgoraMeetingService
{
    public function ensureChannel(ExpertBooking $booking): ExpertBooking
    {
        if ($booking->agora_channel) {
            return $booking;
        }

        $booking->update(['agora_channel' => 'booking-'.$booking->id]);

        return $booking->fresh();
    }

    /**
     * @return array{
     *     app_id: string,
     *     channel: string,
     *     token: string,
     *     uid: int,
     *     role: string,
     *     expires_at: string,
     *     scheduled_starts_at: string,
     *     scheduled_ends_at: string,
     *     join_opens_at: string,
     *     join_closes_at: string,
     *     meeting_joins: array{user: array{status: string, joined_at: string|null}, expert: array{status: string, joined_at: string|null}}
     * }
     */
    public function credentialsFor(User $user, ExpertBooking $booking): array
    {
        $this->assertParticipant($user, $booking);
        $this->assertJoinable($booking);

        $booking = $this->ensureChannel($booking);
        $booking = $this->recordJoin($user, $booking);
        $window = $this->meetingWindow($booking);

        $appId = config('agora.app_id');
        $appCertificate = config('agora.app_certificate');

        if (! $appId || ! $appCertificate) {
            Log::error('Agora credentials are not configured.');

            throw ValidationException::withMessages([
                'meeting' => ['Video meetings are not configured. Please contact support.'],
            ]);
        }

        $uid = (int) $user->id;
        $privilegeExpiresAt = $window['join_closes_at']->timestamp;
        $tokenExpiresAt = max(
            $privilegeExpiresAt,
            now()->addSeconds((int) config('agora.token_ttl_seconds'))->timestamp
        );

        $client = new Agora($appId, $appCertificate);
        $client->setExpiration($tokenExpiresAt);

        $agoraUser = (new AgoraUser($uid))
            ->setChannel($booking->agora_channel)
            ->setRole(Roles::RTC_PUBLISHER)
            ->setPrivilegeExpire($privilegeExpiresAt);

        $token = RtcToken::buildTokenWithUid($client, $agoraUser);

        return [
            'app_id' => $appId,
            'channel' => $booking->agora_channel,
            'token' => $token,
            'uid' => $uid,
            'role' => 'publisher',
            'expires_at' => Carbon::createFromTimestamp($tokenExpiresAt)->toIso8601String(),
            'scheduled_starts_at' => $window['starts_at']->toIso8601String(),
            'scheduled_ends_at' => $window['ends_at']->toIso8601String(),
            'join_opens_at' => $window['join_opens_at']->toIso8601String(),
            'join_closes_at' => $window['join_closes_at']->toIso8601String(),
            'meeting_joins' => $this->normalizeMeetingJoins($booking->meeting_joins),
        ];
    }

    /**
     * @return array{
     *     channel: string|null,
     *     scheduled_starts_at: string,
     *     scheduled_ends_at: string,
     *     join_opens_at: string,
     *     join_closes_at: string,
     *     can_join: bool,
     *     is_live: bool,
     *     meeting_joins: array{user: array{status: string, joined_at: string|null}, expert: array{status: string, joined_at: string|null}}
     * }
     */
    public function summaryFor(User $user, ExpertBooking $booking): array
    {
        $this->assertParticipant($user, $booking);

        $booking = $this->ensureChannel($booking);
        $window = $this->meetingWindow($booking);
        $now = now();

        $canJoin = $booking->status === ExpertBookingStatus::Confirmed
            && $now->greaterThanOrEqualTo($window['join_opens_at'])
            && $now->lessThanOrEqualTo($window['join_closes_at']);

        $isLive = $booking->status === ExpertBookingStatus::Confirmed
            && $now->greaterThanOrEqualTo($window['starts_at'])
            && $now->lessThanOrEqualTo($window['ends_at']);

        return [
            'channel' => $booking->agora_channel,
            'scheduled_starts_at' => $window['starts_at']->toIso8601String(),
            'scheduled_ends_at' => $window['ends_at']->toIso8601String(),
            'join_opens_at' => $window['join_opens_at']->toIso8601String(),
            'join_closes_at' => $window['join_closes_at']->toIso8601String(),
            'can_join' => $canJoin,
            'is_live' => $isLive,
            'meeting_joins' => $this->normalizeMeetingJoins($booking->meeting_joins),
        ];
    }

    public function recordJoin(User $user, ExpertBooking $booking): ExpertBooking
    {
        $role = $this->participantRole($user, $booking);
        if ($role === null) {
            return $booking;
        }

        $joins = $this->normalizeMeetingJoins($booking->meeting_joins);

        if ($joins[$role]['status'] !== 'joined') {
            $joins[$role] = [
                'status' => 'joined',
                'joined_at' => now()->toIso8601String(),
            ];

            $booking->update(['meeting_joins' => $joins]);
        }

        return $booking->fresh();
    }

    /**
     * @return array{
     *     user: array{status: string, joined_at: string|null},
     *     expert: array{status: string, joined_at: string|null}
     * }
     */
    public function normalizeMeetingJoins(?array $joins): array
    {
        $defaults = [
            'user' => ['status' => 'not_joined', 'joined_at' => null],
            'expert' => ['status' => 'not_joined', 'joined_at' => null],
        ];

        if ($joins === null) {
            return $defaults;
        }

        foreach (['user', 'expert'] as $role) {
            $entry = $joins[$role] ?? [];
            $defaults[$role] = [
                'status' => ($entry['status'] ?? 'not_joined') === 'joined' ? 'joined' : 'not_joined',
                'joined_at' => $entry['joined_at'] ?? null,
            ];
        }

        return $defaults;
    }

    private function participantRole(User $user, ExpertBooking $booking): ?string
    {
        if ($booking->user_id === $user->id) {
            return 'user';
        }

        if ($booking->expert_user_id === $user->id) {
            return 'expert';
        }

        return null;
    }

    /**
     * @return array{starts_at: Carbon, ends_at: Carbon, join_opens_at: Carbon, join_closes_at: Carbon}
     */
    public function meetingWindow(ExpertBooking $booking): array
    {
        $timezone = config('app.timezone', 'UTC');

        $startsAt = Carbon::parse(
            $booking->scheduled_date->toDateString().' '.$booking->start_time->format('H:i:s'),
            $timezone
        );

        $endsAt = Carbon::parse(
            $booking->scheduled_date->toDateString().' '.$booking->end_time->format('H:i:s'),
            $timezone
        );

        $joinEarly = (int) config('agora.join_early_minutes', 15);
        $joinLate = (int) config('agora.join_late_minutes', 15);

        // Business rule: allow joining up to 1 hour before the slot start time.
        // This keeps older config values (like 15 minutes) from blocking the flow.
        $joinEarly = max($joinEarly, 60);

        return [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'join_opens_at' => $startsAt->copy()->subMinutes($joinEarly),
            'join_closes_at' => $endsAt->copy()->addMinutes($joinLate),
        ];
    }

    public function assertParticipant(User $user, ExpertBooking $booking): void
    {
        if ($booking->user_id !== $user->id && $booking->expert_user_id !== $user->id) {
            throw ValidationException::withMessages([
                'booking' => ['You are not allowed to access this meeting.'],
            ]);
        }
    }

    public function assertJoinable(ExpertBooking $booking): void
    {
        if ($booking->status === ExpertBookingStatus::Cancelled) {
            throw ValidationException::withMessages([
                'booking' => ['This meeting was cancelled.'],
            ]);
        }

        if ($booking->status !== ExpertBookingStatus::Confirmed) {
            throw ValidationException::withMessages([
                'booking' => ['This meeting is not available.'],
            ]);
        }

        $window = $this->meetingWindow($booking);
        $now = now();

        if ($now->lt($window['join_opens_at'])) {
            throw ValidationException::withMessages([
                'meeting' => ['The meeting room is not open yet. You can join '.$window['join_opens_at']->diffForHumans().'.'],
            ]);
        }

        if ($now->gt($window['join_closes_at'])) {
            throw ValidationException::withMessages([
                'meeting' => ['This meeting has ended.'],
            ]);
        }
    }
}
