<?php

namespace App\Models;

use App\Enums\ExpertApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\FileStorageService;

class ExpertApplication extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'subcategory_id',
        'status',
        'professional_headline',
        'bio',
        'years_of_experience',
        'registration_value',
        'intro_video',
        'languages',
        'avatar',
        'documents',
        'education',
        'experience',
        'portfolio',
        'admin_feedback',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExpertApplicationStatus::class,
            'years_of_experience' => 'integer',
            'languages' => 'array',
            'documents' => 'array',
            'education' => 'array',
            'experience' => 'array',
            'portfolio' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function avatarUrl(): ?string
    {
        return app(FileStorageService::class)->url($this->avatar);
    }

    public function introVideoUrl(): ?string
    {
        if ($this->intro_video === null || $this->intro_video === '') {
            return null;
        }

        if (str_starts_with($this->intro_video, 'http://') || str_starts_with($this->intro_video, 'https://')) {
            return $this->intro_video;
        }

        return app(FileStorageService::class)->url($this->intro_video);
    }

    /**
     * @return list<array{name: string, path: string, url: string|null}>
     */
    public function documentsWithUrls(): array
    {
        $storage = app(FileStorageService::class);

        return collect($this->documents ?? [])
            ->filter(fn ($doc) => is_array($doc) && ! empty($doc['path']))
            ->map(fn (array $doc) => [
                'name' => (string) ($doc['name'] ?? ''),
                'path' => (string) $doc['path'],
                'url' => $storage->url($doc['path']),
            ])
            ->values()
            ->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'expert_application_skill')
            ->withPivot('id')
            ->withTimestamps();
    }
}
