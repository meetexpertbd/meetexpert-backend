<?php

namespace App\Services;

use App\Enums\ExpertApplicationStatus;
use App\Models\ExpertApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpertApplicationService
{
    public function __construct(
        private FileStorageService $fileStorage
    ) {}

    public function submit(
        User $user,
        array $data,
        ?UploadedFile $avatar = null,
        array $documents = [],
        ?UploadedFile $introVideo = null
    ): ExpertApplication
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

        return DB::transaction(function () use ($user, $data, $skillIds, $avatar, $documents, $introVideo) {
            $payload = [
                'user_id' => $user->id,
                'category_id' => (int) $data['category_id'],
                'subcategory_id' => (int) $data['subcategory_id'],
                'professional_headline' => $data['professional_headline'],
                'bio' => $data['bio'],
                'years_of_experience' => (int) $data['years_of_experience'],
                'registration_value' => $data['registration_value'],
                'languages' => array_values($data['languages']),
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

            $directory = 'expert-applications/'.$user->id;

            if ($introVideo !== null) {
                $this->deleteStoredIntroVideo($existing?->intro_video);
                $payload['intro_video'] = $this->fileStorage->store($introVideo, $directory.'/intro-video');
            } elseif (array_key_exists('intro_video', $data)) {
                $url = is_string($data['intro_video']) ? trim($data['intro_video']) : null;
                $url = $url !== '' ? $url : null;
                if ($url !== $existing?->intro_video) {
                    $this->deleteStoredIntroVideo($existing?->intro_video);
                }
                $payload['intro_video'] = $url;
            } elseif ($existing) {
                $payload['intro_video'] = $existing->intro_video;
            } else {
                $payload['intro_video'] = null;
            }

            if ($avatar !== null) {
                if ($existing?->avatar) {
                    $this->fileStorage->delete($existing->avatar);
                }
                $payload['avatar'] = $this->fileStorage->store($avatar, $directory.'/avatar');
            } elseif ($existing) {
                $payload['avatar'] = $existing->avatar;
            }

            if ($documents !== []) {
                if ($existing?->documents) {
                    foreach ($existing->documents as $doc) {
                        if (is_array($doc) && ! empty($doc['path'])) {
                            $this->fileStorage->delete($doc['path']);
                        }
                    }
                }
                $payload['documents'] = $this->storeDocuments($documents, $directory.'/documents');
            } elseif ($existing) {
                $payload['documents'] = $existing->documents;
            }

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

    private function deleteStoredIntroVideo(?string $value): void
    {
        if ($value === null || $value === '' || $this->isExternalUrl($value)) {
            return;
        }

        $this->fileStorage->delete($value);
    }

    private function isExternalUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    /**
     * @param  list<array{name: string, file: UploadedFile}>  $documents
     * @return list<array{name: string, path: string}>
     */
    private function storeDocuments(array $documents, string $directory): array
    {
        $stored = [];

        foreach ($documents as $document) {
            $file = $document['file'] ?? null;
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $stored[] = [
                'name' => trim((string) ($document['name'] ?? '')),
                'path' => $this->fileStorage->store($file, $directory),
            ];
        }

        return $stored;
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
