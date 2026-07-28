<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\StoreTaxonomyBulkUploadRequest;
use App\Services\TaxonomyBulkUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxonomyBulkUploadController extends Controller
{
    public function __construct(
        private TaxonomyBulkUploadService $taxonomyBulkUploadService
    ) {}

    public function create(): View
    {
        return view('pages.taxonomy.bulk-upload.index', [
            'title' => 'Bulk upload',
        ]);
    }

    public function store(StoreTaxonomyBulkUploadRequest $request): RedirectResponse
    {
        $stats = $this->taxonomyBulkUploadService->import($request->payload());

        $message = sprintf(
            'Bulk upload complete. Created: %d categories, %d subcategories, %d skills. Skipped existing: %d categories, %d subcategories, %d skills.',
            $stats['categories_created'],
            $stats['subcategories_created'],
            $stats['skills_created'],
            $stats['categories_skipped'],
            $stats['subcategories_skipped'],
            $stats['skills_skipped'],
        );

        return redirect()
            ->route('taxonomy.bulk-upload.create')
            ->with('success', $message)
            ->withInput(['json' => $request->input('json')]);
    }
}
