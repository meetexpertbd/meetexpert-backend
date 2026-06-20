<?php

namespace App\Models;

use App\Enums\ExpertApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExpertApplication extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'subcategory_id',
        'status',
        'professional_headline',
        'bio',
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
            'education' => 'array',
            'experience' => 'array',
            'portfolio' => 'array',
            'reviewed_at' => 'datetime',
        ];
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
