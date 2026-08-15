<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\BulkDestroyRequest;
use App\Http\Requests\Admin\V1\StoreCategoryRequest;
use App\Http\Requests\Admin\V1\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('pages.taxonomy.categories.index', [
            'title' => 'Categories',
            'categories' => $categories,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->slugFromName($data['name'], null);
        $data['code_prefix'] = $this->normalizeCodePrefix($data['code_prefix']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        Category::query()->create($data);

        return redirect()
            ->route('taxonomy.categories.index')
            ->with('success', 'Category created.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->slugFromName($data['name'], $category);
        $data['code_prefix'] = $this->normalizeCodePrefix($data['code_prefix']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return redirect()
            ->route('taxonomy.categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()
            ->route('taxonomy.categories.index')
            ->with('danger', 'Category deleted.');
    }

    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse
    {
        return $this->bulkDeleteRedirect(
            Category::query()->whereIn('id', $request->ids())->get(),
            'taxonomy.categories.index',
            'Category deleted.',
            'categories deleted.',
            'Categories could not be deleted because they are in use.'
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category>  $items
     */
    private function bulkDeleteRedirect($items, string $route, string $one, string $many, string $blockedMessage): RedirectResponse
    {
        $deleted = 0;
        $blocked = 0;

        foreach ($items as $item) {
            try {
                $item->delete();
                $deleted++;
            } catch (QueryException) {
                $blocked++;
            }
        }

        if ($deleted === 0) {
            return redirect()->route($route)->with('danger', $blocked > 0 ? $blockedMessage : 'No records were deleted.');
        }

        $message = $deleted === 1 ? $one : $deleted.' '.$many;
        if ($blocked > 0) {
            $message .= ' '.$blockedMessage;
        }

        return redirect()->route($route)->with($blocked > 0 ? 'info' : 'danger', $message);
    }

    private function normalizeCodePrefix(string $prefix): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?? '');
    }

    private function slugFromName(string $name, ?Category $except): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'category';
        }

        $candidate = $base;
        $n = 0;
        while (Category::query()
            ->where('slug', $candidate)
            ->when($except, fn ($q) => $q->where('id', '!=', $except->id))
            ->exists()) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }
}
