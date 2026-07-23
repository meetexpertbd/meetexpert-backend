<?php

namespace App\Models;

use App\Enums\UserGender;
use App\Services\FileStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'gender',
        'date_of_birth',
        'phone',
        'avatar_path',
        'present_address',
        'permanent_address',
        'district',
        'country',
        'preferred_language',
    ];

    protected function casts(): array
    {
        return [
            'gender' => UserGender::class,
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function avatarUrl(): ?string
    {
        return app(FileStorageService::class)->url($this->avatar_path);
    }
}
