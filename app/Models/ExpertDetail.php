<?php

namespace App\Models;

use App\Enums\ExpertDetailStatus;
use App\Services\FileStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExpertDetail extends Model
{
    protected $table = 'experts_details';

    protected $fillable = [
        'uuid',
        'user_id',
        'expert_application_id',
        'category_id',
        'subcategory_id',
        'expert_code',
        'slug',
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
    ];

    protected function casts(): array
    {
        return [
            'status' => ExpertDetailStatus::class,
            'years_of_experience' => 'integer',
            'languages' => 'array',
            'documents' => 'array',
            'education' => 'array',
            'experience' => 'array',
            'portfolio' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ExpertApplication::class, 'expert_application_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'expert_detail_skill')
            ->withPivot('id')
            ->withTimestamps();
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
}
