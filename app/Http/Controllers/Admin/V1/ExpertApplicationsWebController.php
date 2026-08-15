<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\BulkDestroyRequest;
use App\Http\Requests\Admin\V1\ReviewExpertApplicationRequest;
use App\Models\ExpertApplication;
use App\Services\ExpertApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpertApplicationsWebController extends Controller
{
    public function __construct(
        private ExpertApplicationService $expertApplicationService
    ) {}

    public function index(): View
    {
        $applications = ExpertApplication::query()
            ->with(['user', 'category', 'subcategory', 'skills'])
            ->latest()
            ->get();

        return view('pages.admin.expert-applications.index', [
            'title' => 'Expert applications',
            'applications' => $applications,
        ]);
    }

    public function show(ExpertApplication $expert_application): View
    {
        $expert_application->load(['user', 'category', 'subcategory', 'skills', 'reviewedBy']);

        return view('pages.admin.expert-applications.show', [
            'title' => 'Expert application #'.$expert_application->id,
            'application' => $expert_application,
        ]);
    }

    public function approve(ReviewExpertApplicationRequest $request, ExpertApplication $expert_application): RedirectResponse
    {
        $detail = $this->expertApplicationService->approveByAdmin(
            $request->user(),
            $expert_application,
            $request->validated('note')
        );

        return redirect()
            ->route('admin.experts.show', $detail->user_id)
            ->with('success', 'Application approved. Expert profile created.');
    }

    public function reject(ReviewExpertApplicationRequest $request, ExpertApplication $expert_application): RedirectResponse
    {
        $this->expertApplicationService->rejectByAdmin(
            $request->user(),
            $expert_application,
            $request->validated('note')
        );

        return redirect()
            ->route('admin.expert-applications.show', $expert_application)
            ->with('success', 'Application rejected.');
    }

    public function destroy(ExpertApplication $expert_application): RedirectResponse
    {
        $expert_application->skills()->detach();
        $expert_application->delete();

        return redirect()
            ->route('admin.expert-applications.index')
            ->with('danger', 'Application deleted.');
    }

    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse
    {
        $applications = ExpertApplication::query()->whereIn('id', $request->ids())->get();
        $count = $applications->count();

        foreach ($applications as $application) {
            $application->skills()->detach();
            $application->delete();
        }

        if ($count === 0) {
            return redirect()
                ->route('admin.expert-applications.index')
                ->with('danger', 'No applications were deleted.');
        }

        return redirect()
            ->route('admin.expert-applications.index')
            ->with('danger', $count === 1 ? 'Application deleted.' : $count.' applications deleted.');
    }
}
