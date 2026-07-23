<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ExpertApplicationStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    public const USER_TYPE_ADMIN = 'admin';

    public const USER_TYPE_USER = 'user';

    public const USER_TYPE_EXPERT = 'expert';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function expertApplications(): HasMany
    {
        return $this->hasMany(ExpertApplication::class);
    }

    public function approvedExpertApplication(): HasOne
    {
        return $this->hasOne(ExpertApplication::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('status', ExpertApplicationStatus::Approved)
        );
    }

    public function expertAvailabilitySlots(): HasMany
    {
        return $this->hasMany(ExpertAvailabilitySlot::class);
    }

    public function expertBookings(): HasMany
    {
        return $this->hasMany(ExpertBooking::class);
    }

    public function receivedExpertBookings(): HasMany
    {
        return $this->hasMany(ExpertBooking::class, 'expert_user_id');
    }
}
