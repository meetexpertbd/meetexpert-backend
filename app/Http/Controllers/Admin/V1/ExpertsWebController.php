<?php

namespace App\Http\Controllers\Admin\V1;

use App\Enums\ExpertDetailStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Services\ExpertDetailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpertsWebController extends Controller
{
    public function __construct(
        private ExpertDetailService $expertDetailService
    ) {}

    public function index(): View
    {
        $experts = User::query()
            ->where('user_type', User::USER_TYPE_EXPERT)
            ->with('expertDetail')
            ->withCount(['expertAvailabilitySlots'])
            ->orderBy('name')
            ->paginate(20);

        return view('pages.admin.experts.index', [
            'title' => 'Experts',
            'experts' => $experts,
        ]);
    }

    public function show(User $user): View
    {
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            abort(404);
        }

        $user->load([
            'expertDetail.category',
            'expertDetail.subcategory',
            'expertDetail.skills',
            'expertAvailabilitySlots' => fn ($q) => $q->orderBy('day_of_week')->orderBy('start_time'),
        ]);

        return view('pages.admin.experts.show', [
            'title' => 'Expert: '.$user->name,
            'expert' => $user,
        ]);
    }

    public function edit(User $user): View|RedirectResponse
    {
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            abort(404);
        }

        $user->load(['expertDetail.skills', 'expertDetail.category', 'expertDetail.subcategory']);

        if ($user->expertDetail === null) {
            return redirect()
                ->route('admin.experts.show', $user)
                ->with('danger', 'This expert has no profile details to edit.');
        }

        $categoriesPayload = $this->categoriesPayload();
        $detail = $user->expertDetail;

        $introVideo = (string) ($detail->intro_video ?? '');
        $introVideoType = 'none';
        $introVideoUrl = '';
        if ($introVideo !== '') {
            if (str_starts_with($introVideo, 'http://') || str_starts_with($introVideo, 'https://')) {
                $introVideoType = 'url';
                $introVideoUrl = $introVideo;
            } else {
                $introVideoType = 'keep';
            }
        }

        return view('pages.admin.experts.edit', [
            'title' => 'Edit expert: '.$user->name,
            'expert' => $user,
            'detail' => $detail,
            'categoriesPayload' => $categoriesPayload,
            'introVideoType' => $introVideoType,
            'introVideoUrl' => $introVideoUrl,
            'statuses' => ExpertDetailStatus::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            abort(404);
        }

        $detail = $user->expertDetail;
        if ($detail === null) {
            return redirect()
                ->route('admin.experts.show', $user)
                ->with('danger', 'This expert has no profile details to edit.');
        }

        if (is_string($request->input('languages'))) {
            $languages = collect(explode(',', $request->input('languages')))
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->all();
            $request->merge(['languages' => $languages]);
        }

        $request->merge([
            'education' => collect($request->input('education', []))
                ->filter(fn ($row) => filled($row['institution'] ?? null))
                ->values()
                ->all() ?: null,
            'experience' => collect($request->input('experience', []))
                ->filter(fn ($row) => filled($row['title'] ?? null))
                ->values()
                ->all() ?: null,
            'portfolio' => collect($request->input('portfolio', []))
                ->filter(fn ($row) => filled($row['url'] ?? null))
                ->values()
                ->all() ?: null,
        ]);

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'subcategory_id' => [
                'required',
                'integer',
                Rule::exists('subcategories', 'id')->where('category_id', $request->input('category_id')),
            ],
            'status' => ['required', Rule::enum(ExpertDetailStatus::class)],
            'professional_headline' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string', 'max:10000'],
            'years_of_experience' => ['required', 'integer', 'min:0', 'max:80'],
            'registration_value' => ['required', 'string', 'max:255'],
            'intro_video_type' => ['nullable', 'in:none,url,file,keep'],
            'intro_video_url' => ['nullable', 'required_if:intro_video_type,url', 'url', 'max:2048'],
            'intro_video_file' => ['nullable', 'required_if:intro_video_type,file', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo', 'max:51200'],
            'languages' => ['required', 'array', 'min:1', 'max:20'],
            'languages.*' => ['required', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*.name' => ['nullable', 'string', 'max:255'],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
            'education' => ['nullable', 'array', 'max:20'],
            'education.*.institution' => ['required_with:education', 'string', 'max:255'],
            'education.*.degree' => ['nullable', 'string', 'max:255'],
            'education.*.year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'experience' => ['nullable', 'array', 'max:30'],
            'experience.*.title' => ['required_with:experience', 'string', 'max:255'],
            'experience.*.organization' => ['nullable', 'string', 'max:255'],
            'experience.*.start_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'experience.*.end_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'experience.*.description' => ['nullable', 'string', 'max:2000'],
            'portfolio' => ['nullable', 'array', 'max:20'],
            'portfolio.*.title' => ['nullable', 'string', 'max:255'],
            'portfolio.*.url' => ['required', 'url', 'max:2048'],
            'skill_ids' => ['required', 'array', 'min:1', 'max:50'],
            'skill_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('skills', 'id')
                    ->where('subcategory_id', $request->input('subcategory_id'))
                    ->where('is_active', true),
            ],
        ]);

        $documents = [];
        foreach ($request->file('documents', []) as $index => $documentFiles) {
            $file = $documentFiles['file'] ?? null;
            $name = $request->input("documents.{$index}.name");
            if ($file === null || ! filled($name)) {
                continue;
            }
            $documents[] = [
                'name' => $name,
                'file' => $file,
            ];
        }

        $introVideoFile = null;
        $payload = collect($validated)
            ->except(['intro_video_type', 'intro_video_url', 'intro_video_file', 'avatar', 'documents'])
            ->all();

        $introType = $validated['intro_video_type'] ?? 'keep';
        if ($introType === 'file') {
            $introVideoFile = $request->file('intro_video_file');
        } elseif ($introType === 'url') {
            $payload['intro_video'] = $validated['intro_video_url'] ?? null;
        } elseif ($introType === 'none') {
            $payload['intro_video'] = null;
        }

        try {
            $this->expertDetailService->updateByAdmin(
                $detail,
                $payload,
                $request->file('avatar'),
                $documents,
                $introVideoFile
            );
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('admin.experts.show', $user)
            ->with('success', 'Expert details updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            abort(404);
        }

        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.experts.index')
                ->with('danger', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.experts.index')
            ->with('danger', $name.' deleted.');
    }

    /**
     * @return list<array{id: int, name: string, subcategories: list<array{id: int, name: string, skills: list<array{id: int, name: string}>}>}>
     */
    private function categoriesPayload(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->with([
                'subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('name')->with([
                    'skills' => fn ($sq) => $sq->where('is_active', true)->orderBy('name'),
                ]),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'subcategories' => $c->subcategories->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'skills' => $s->skills->map(fn ($skill) => [
                        'id' => $skill->id,
                        'name' => $skill->name,
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
