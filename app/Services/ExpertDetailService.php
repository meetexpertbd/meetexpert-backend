<?php

namespace App\Services;

use App\Enums\ExpertDetailStatus;
use App\Models\Category;
use App\Models\ExpertApplication;
use App\Models\ExpertDetail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpertDetailService
{
    public function __construct(
        private FileStorageService $fileStorage
    ) {}

    public function createFromApprovedApplication(ExpertApplication $application): ExpertDetail
    {
        $application->loadMissing(['user', 'category', 'skills']);

        if (ExpertDetail::query()->where('user_id', $application->user_id)->exists()) {
            throw ValidationException::withMessages([
                'application' => ['Expert details already exist for this user.'],
            ]);
        }

        $category = $application->category;
        $prefix = trim((string) ($category?->code_prefix ?? ''));
        if ($prefix === '') {
            throw ValidationException::withMessages([
                'category_id' => ['Category code prefix is required before approving an expert. Set it on the category first.'],
            ]);
        }

        $expertCode = $this->generateUniqueExpertCode($prefix);
        $slug = $this->generateUniqueSlug($category, $application->user, $expertCode);

        $detail = ExpertDetail::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $application->user_id,
            'expert_application_id' => null,
            'category_id' => $application->category_id,
            'subcategory_id' => $application->subcategory_id,
            'expert_code' => $expertCode,
            'slug' => $slug,
            'status' => ExpertDetailStatus::Active,
            'professional_headline' => $application->professional_headline,
            'bio' => $application->bio,
            'years_of_experience' => $application->years_of_experience,
            'registration_value' => $application->registration_value,
            'intro_video' => $application->intro_video,
            'languages' => $application->languages,
            'avatar' => $application->avatar,
            'documents' => $application->documents,
            'education' => $application->education,
            'experience' => $application->experience,
            'portfolio' => $application->portfolio,
        ]);

        $skillIds = $application->skills->pluck('id')->all();
        if ($skillIds !== []) {
            $detail->skills()->attach($skillIds);
        }

        return $detail->load(['category', 'subcategory', 'skills', 'user']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{name: string, file: UploadedFile}>  $documents
     */
    public function updateByAdmin(
        ExpertDetail $detail,
        array $data,
        ?UploadedFile $avatar = null,
        array $documents = [],
        ?UploadedFile $introVideo = null
    ): ExpertDetail {
        $detail->loadMissing(['user', 'category']);

        $skillIds = array_values(array_unique(array_map('intval', $data['skill_ids'] ?? [])));
        $category = Category::query()->findOrFail((int) $data['category_id']);

        return DB::transaction(function () use ($detail, $data, $skillIds, $avatar, $documents, $introVideo, $category) {
            $directory = 'experts/'.$detail->user_id;
            $payload = [
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
            ];

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $payload['status'] = $data['status'] instanceof ExpertDetailStatus
                    ? $data['status']
                    : ExpertDetailStatus::from((string) $data['status']);
            }

            if ((int) $detail->category_id !== (int) $data['category_id']) {
                $payload['slug'] = $this->generateUniqueSlug(
                    $category,
                    $detail->user,
                    $detail->expert_code,
                    $detail->id
                );
            }

            if ($introVideo !== null) {
                $this->deleteStoredIntroVideo($detail->intro_video);
                $payload['intro_video'] = $this->fileStorage->store($introVideo, $directory.'/intro-video');
            } elseif (array_key_exists('intro_video', $data)) {
                $url = is_string($data['intro_video']) ? trim($data['intro_video']) : null;
                $url = $url !== '' ? $url : null;
                if ($url !== $detail->intro_video) {
                    $this->deleteStoredIntroVideo($detail->intro_video);
                }
                $payload['intro_video'] = $url;
            }

            if ($avatar !== null) {
                if ($detail->avatar) {
                    $this->fileStorage->delete($detail->avatar);
                }
                $payload['avatar'] = $this->fileStorage->store($avatar, $directory.'/avatar');
            }

            if ($documents !== []) {
                foreach ($detail->documents ?? [] as $doc) {
                    if (is_array($doc) && ! empty($doc['path'])) {
                        $this->fileStorage->delete($doc['path']);
                    }
                }
                $payload['documents'] = $this->storeDocuments($documents, $directory.'/documents');
            }

            $detail->update($payload);
            $detail->skills()->sync($skillIds);

            return $detail->fresh()->load(['category', 'subcategory', 'skills', 'user']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{name: string, file: UploadedFile}>  $documents
     */
    public function updateByExpert(
        ExpertDetail $detail,
        array $data,
        ?UploadedFile $avatar = null,
        array $documents = [],
        ?UploadedFile $introVideo = null
    ): ExpertDetail {
        unset($data['status']);

        return $this->updateByAdmin($detail, $data, $avatar, $documents, $introVideo);
    }

    private function generateUniqueExpertCode(string $prefix): string
    {
        $prefix = strtoupper(rtrim($prefix, '-'));

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $digits = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $code = $prefix.'-'.$digits;

            if (! ExpertDetail::query()->where('expert_code', $code)->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'expert_code' => ['Unable to generate a unique expert code. Please try again.'],
        ]);
    }

    private function generateUniqueSlug(
        Category $category,
        User $user,
        string $expertCode,
        ?int $ignoreDetailId = null
    ): string {
        $base = Str::slug(implode('-', array_filter([
            $category->slug ?: $category->name,
            $user->name,
            $expertCode,
        ])));

        if ($base === '') {
            $base = 'expert-'.$expertCode;
        }

        $candidate = $base;
        $n = 0;
        while (
            ExpertDetail::query()
                ->where('slug', $candidate)
                ->when($ignoreDetailId, fn ($q) => $q->where('id', '!=', $ignoreDetailId))
                ->exists()
        ) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
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
}
