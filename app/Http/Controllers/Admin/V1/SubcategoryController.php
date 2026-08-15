<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\BulkDestroyRequest;
use App\Http\Requests\Admin\V1\StoreSubcategoryRequest;
use App\Http\Requests\Admin\V1\UpdateSubcategoryRequest;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubcategoryController extends Controller
{
    public function index(): View
    {
        $subcategories = Subcategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $parentCategories = Category::query()->orderBy('name')->get();

        return view('pages.taxonomy.subcategories.index', [
            'title' => 'Subcategories',
            'subcategories' => $subcategories,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function store(StoreSubcategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->slugFromName(
            $data['name'],
            (int) $data['category_id'],
            null
        );
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        Subcategory::query()->create($data);

        return redirect()
            ->route('taxonomy.subcategories.index')
            ->with('success', 'Subcategory created.');
    }

    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->slugFromName(
            $data['name'],
            (int) $data['category_id'],
            $subcategory
        );
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        $subcategory->update($data);

        return redirect()
            ->route('taxonomy.subcategories.index')
            ->with('success', 'Subcategory updated.');
    }

    public function destroy(Subcategory $subcategory): RedirectResponse
    {
        $subcategory->delete();

        return redirect()
            ->route('taxonomy.subcategories.index')
            ->with('danger', 'Subcategory deleted.');
    }

    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse
    {
        $deleted = 0;
        $blocked = 0;

        foreach (Subcategory::query()->whereIn('id', $request->ids())->get() as $subcategory) {
            try {
                $subcategory->delete();
                $deleted++;
            } catch (QueryException) {
                $blocked++;
            }
        }

        if ($deleted === 0) {
            return redirect()
                ->route('taxonomy.subcategories.index')
                ->with('danger', $blocked > 0
                    ? 'Subcategories could not be deleted because they are in use.'
                    : 'No records were deleted.');
        }

        $message = $deleted === 1 ? 'Subcategory deleted.' : $deleted.' subcategories deleted.';
        if ($blocked > 0) {
            $message .= ' Some could not be deleted because they are in use.';
        }

        return redirect()
            ->route('taxonomy.subcategories.index')
            ->with($blocked > 0 ? 'info' : 'danger', $message);
    }

    private function slugFromName(string $name, int $categoryId, ?Subcategory $except): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'subcategory';
        }

        $candidate = $base;
        $n = 0;
        while (Subcategory::query()
            ->where('category_id', $categoryId)
            ->where('slug', $candidate)
            ->when($except, fn ($q) => $q->where('id', '!=', $except->id))
            ->exists()) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }
}
