<?php

namespace App\Services;

use App\Enums\ExpertDetailStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpertDiscoveryService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 20), 100);

        return User::query()
            ->where('user_type', User::USER_TYPE_EXPERT)
            ->whereHas('expertDetail', function ($query) use ($filters): void {
                $query->where('status', ExpertDetailStatus::Active);

                if (! empty($filters['category_id'])) {
                    $query->where('category_id', (int) $filters['category_id']);
                }
                if (! empty($filters['subcategory_id'])) {
                    $query->where('subcategory_id', (int) $filters['subcategory_id']);
                }
            })
            ->with([
                'expertDetail.category',
                'expertDetail.subcategory',
                'expertDetail.skills',
            ])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findPublicExpert(User $user): ?User
    {
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            return null;
        }

        $user->load([
            'expertDetail.category',
            'expertDetail.subcategory',
            'expertDetail.skills',
        ]);

        if (! $user->expertDetail || $user->expertDetail->status !== ExpertDetailStatus::Active) {
            return null;
        }

        return $user;
    }
}
