<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\StoreSkillsRequest;
use App\Http\Requests\Admin\V1\UpdateSkillRequest;
use App\Models\Category;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function index(): View
    {
        $skills = Skill::query()
            ->with(['subcategory.category'])
            ->orderBy('subcategory_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $categoriesPayload = Category::query()
            ->with(['subcategories' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'subcategories' => $c->subcategories->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return view('pages.taxonomy.skills.index', [
            'title' => 'Skills',
            'skills' => $skills,
            'categoriesPayload' => $categoriesPayload,
        ]);
    }

    public function store(StoreSkillsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $subcategoryId = (int) $data['subcategory_id'];
        $byKey = [];
        foreach (array_map('trim', $data['names']) as $n) {
            if ($n === '') {
                continue;
            }
            $k = mb_strtolower($n);
            if (! isset($byKey[$k])) {
                $byKey[$k] = $n;
            }
        }
        $names = array_values($byKey);

        DB::transaction(function () use ($names, $subcategoryId): void {
            foreach ($names as $name) {
                Skill::query()->create([
                    'subcategory_id' => $subcategoryId,
                    'name' => $name,
                    'slug' => $this->slugFromName($name, $subcategoryId, null),
                    'description' => null,
                    'sort_order' => 0,
                    'is_active' => true,
                ]);
            }
        });

        $count = count($names);

        return redirect()
            ->route('taxonomy.skills.index')
            ->with('success', $count === 1 ? 'Skill created.' : $count.' skills created.');
    }

    public function update(UpdateSkillRequest $request, Skill $skill): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->slugFromName(
            $data['name'],
            (int) $data['subcategory_id'],
            $skill
        );
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        $skill->update($data);

        return redirect()
            ->route('taxonomy.skills.index')
            ->with('success', 'Skill updated.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        return redirect()
            ->route('taxonomy.skills.index')
            ->with('danger', 'Skill deleted.');
    }

    private function slugFromName(string $name, int $subcategoryId, ?Skill $except): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'skill';
        }

        $candidate = $base;
        $n = 0;
        while (Skill::query()
            ->where('subcategory_id', $subcategoryId)
            ->where('slug', $candidate)
            ->when($except, fn ($q) => $q->where('id', '!=', $except->id))
            ->exists()) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }
}
