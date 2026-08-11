<?php

namespace App\Services;

use App\Enums\ExpertDetailStatus;
use App\Models\ExpertDetail;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ExpertDiscoveryService
{
    /**
     * @param  array{
     *     category_id?: int,
     *     subcategory_id?: int,
     *     skill_ids?: list<int>|null,
     *     per_page?: int
     * }  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 20), 1), 100);
        $skillIds = array_values(array_unique(array_map(
            'intval',
            $filters['skill_ids'] ?? []
        )));

        return User::query()
            ->where('user_type', User::USER_TYPE_EXPERT)
            ->whereHas('expertDetail', function (Builder $query) use ($filters, $skillIds): void {
                $query->where('status', ExpertDetailStatus::Active);

                if (! empty($filters['category_id'])) {
                    $query->where('category_id', (int) $filters['category_id']);
                }

                if (! empty($filters['subcategory_id'])) {
                    $query->where('subcategory_id', (int) $filters['subcategory_id']);
                }

                if ($skillIds !== []) {
                    $query->whereHas(
                        'skills',
                        fn (Builder $skills) => $skills->whereIn('skills.id', $skillIds)
                    );
                }
            })
            ->with([
                'expertDetail.category:id,name,slug,code_prefix',
                'expertDetail.subcategory:id,category_id,name,slug',
                'expertDetail.skills:id,subcategory_id,name,slug',
                'expertSlotPrice',
            ])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPublicExpert(User $user): ?User
    {
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            return null;
        }

        $user->load([
            'expertDetail.category:id,name,slug,code_prefix',
            'expertDetail.subcategory:id,category_id,name,slug',
            'expertDetail.skills:id,subcategory_id,name,slug',
            'expertSlotPrice',
        ]);

        if (! $user->expertDetail || $user->expertDetail->status !== ExpertDetailStatus::Active) {
            return null;
        }

        return $user;
    }

    public function findPublicExpertBySlug(string $slug): ?User
    {
        $detail = ExpertDetail::query()
            ->where('slug', $slug)
            ->where('status', ExpertDetailStatus::Active)
            ->first();

        if ($detail === null || $detail->user === null) {
            return null;
        }

        return $this->findPublicExpert($detail->user);
    }
}
