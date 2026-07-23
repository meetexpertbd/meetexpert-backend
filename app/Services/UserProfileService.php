<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserProfileService
{
    /**
     * @param  array{
     *     gender?: string|null,
     *     date_of_birth?: string|null,
     *     phone?: string|null,
     *     present_address?: string|null,
     *     permanent_address?: string|null,
     *     district?: string|null,
     *     country?: string|null,
     *     preferred_language?: string|null
     * }  $data
     */
    public function update(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($user, $data, $avatar): User {
            /** @var UserProfile $profile */
            $profile = UserProfile::query()->firstOrNew(['user_id' => $user->id]);

            $profileFields = collect($data)
                ->only([
                    'gender',
                    'date_of_birth',
                    'phone',
                    'present_address',
                    'permanent_address',
                    'district',
                    'country',
                    'preferred_language',
                ])
                ->all();

            $profile->fill($profileFields);

            if ($avatar !== null) {
                if ($profile->avatar_path) {
                    Storage::disk('public')->delete($profile->avatar_path);
                }
                $profile->avatar_path = $avatar->store('avatars/'.$user->id, 'public');
            }

            if (! $profile->exists) {
                $profile->country = $profile->country ?: 'BD';
                $profile->preferred_language = $profile->preferred_language ?: 'bn';
            }

            $profile->save();

            return $user->fresh()->load('profile');
        });
    }
}
