<?php

namespace App\Services;

use App\Enums\ExpertApplicationStatus;
use App\Models\ExpertApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpertApplicationService
{
    public function submit(User $user, array $data): ExpertApplication
    {
        if ($user->user_type === User::USER_TYPE_EXPERT) {
            throw ValidationException::withMessages([
                'user' => ['You are already registered as an expert.'],
            ]);
        }

        if ($user->user_type === User::USER_TYPE_ADMIN) {
            throw ValidationException::withMessages([
                'user' => ['Administrator accounts cannot submit expert applications.'],
            ]);
        }

        if (ExpertApplication::query()
            ->where('user_id', $user->id)
            ->where('status', ExpertApplicationStatus::Pending)
            ->exists()) {
            throw ValidationException::withMessages([
                'application' => ['You already have an application under review.'],
            ]);
        }

        $skillIds = array_values(array_unique(array_map('intval', $data['skill_ids'])));

        return DB::transaction(function () use ($user, $data, $skillIds) {
            $payload = [
                'user_id' => $user->id,
                'category_id' => (int) $data['category_id'],
                'subcategory_id' => (int) $data['subcategory_id'],
                'professional_headline' => $data['professional_headline'],
                'bio' => $data['bio'],
                'education' => $data['education'] ?? null,
                'experience' => $data['experience'] ?? null,
                'portfolio' => $data['portfolio'] ?? null,
                'status' => ExpertApplicationStatus::Pending,
                'admin_feedback' => null,
                'reviewed_at' => null,
                'reviewed_by_user_id' => null,
            ];

            $existing = ExpertApplication::query()
                ->where('user_id', $user->id)
                ->where('status', ExpertApplicationStatus::NeedsCorrection)
                ->latest()
                ->first();

            if ($existing) {
                $existing->update($payload);
                $existing->skills()->sync($skillIds);

                return $existing->load(['category', 'subcategory', 'skills']);
            }

            $application = ExpertApplication::query()->create($payload);
            $application->skills()->attach($skillIds);

            return $application->load(['category', 'subcategory', 'skills']);
        });
    }

    /**
     * @throws ValidationException
     */
    public function approveByAdmin(User $admin, ExpertApplication $application, string $note): ExpertApplication
    {
        return DB::transaction(function () use ($admin, $application, $note) {
            $application = ExpertApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $this->assertReviewable($application);

            $user = User::query()->whereKey($application->user_id)->lockForUpdate()->firstOrFail();
            if ($user->user_type === User::USER_TYPE_EXPERT) {
                throw ValidationException::withMessages([
                    'note' => ['This user is already registered as an expert.'],
                ]);
            }

            $application->update([
                'status' => ExpertApplicationStatus::Approved,
                'admin_feedback' => $note,
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $admin->id,
            ]);

            $user->update(['user_type' => User::USER_TYPE_EXPERT]);

            return $application->fresh(['user', 'category', 'subcategory', 'skills', 'reviewedBy']);
        });
    }

    /**
     * @throws ValidationException
     */
    public function rejectByAdmin(User $admin, ExpertApplication $application, string $note): ExpertApplication
    {
        return DB::transaction(function () use ($admin, $application, $note) {
            $application = ExpertApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $this->assertReviewable($application);

            $application->update([
                'status' => ExpertApplicationStatus::Rejected,
                'admin_feedback' => $note,
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $admin->id,
            ]);

            return $application->fresh(['user', 'category', 'subcategory', 'skills', 'reviewedBy']);
        });
    }

    private function assertReviewable(ExpertApplication $application): void
    {
        $allowed = [
            ExpertApplicationStatus::Pending,
            ExpertApplicationStatus::NeedsCorrection,
        ];

        if (! in_array($application->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'note' => ['This application has already been reviewed.'],
            ]);
        }
    }
}
