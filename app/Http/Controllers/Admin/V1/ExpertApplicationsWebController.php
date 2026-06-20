<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
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
            ->paginate(20);

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
        $this->expertApplicationService->approveByAdmin(
            $request->user(),
            $expert_application,
            $request->validated('note')
        );

        return redirect()
            ->route('admin.expert-applications.show', $expert_application)
            ->with('success', 'Application approved.');
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
}
