<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Enums\RegistrationFrom;
use App\Models\Category;
use App\Models\User;
use App\Services\ExpertApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsersWebController extends Controller
{
    public function __construct(
        private ExpertApplicationService $expertApplicationService
    ) {}

    public function index(): View
    {
        $users = User::query()
            ->where('user_type', User::USER_TYPE_USER)
            ->withExists('expertApplications')
            ->orderBy('name')
            ->paginate(25);

        return view('pages.admin.users.index', [
            'title' => 'Users',
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'user_type' => User::USER_TYPE_USER,
            'registration_from' => RegistrationFrom::AdminPanel,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created.');
    }

    public function applyForExpert(User $user): View|RedirectResponse
    {
        if ($user->user_type !== User::USER_TYPE_USER) {
            return redirect()
                ->route('admin.users.index')
                ->with('danger', 'Only standard users can be converted to experts.');
        }

        if ($user->expertApplications()->exists()) {
            return redirect()
                ->route('admin.users.index')
                ->with('info', $user->name.' already has an expert application.');
        }

        $categoriesPayload = Category::query()
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

        return view('pages.admin.users.apply-for-expert', [
            'title' => 'Apply for expert',
            'user' => $user,
            'categoriesPayload' => $categoriesPayload,
        ]);
    }

    public function storeExpertApplication(Request $request, User $user): RedirectResponse
    {
        if ($user->user_type !== User::USER_TYPE_USER) {
            return redirect()
                ->route('admin.users.index')
                ->with('danger', 'Only standard users can be converted to experts.');
        }

        if ($user->expertApplications()->exists()) {
            return redirect()
                ->route('admin.users.index')
                ->with('info', $user->name.' already has an expert application.');
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
            'professional_headline' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string', 'max:10000'],
            'years_of_experience' => ['required', 'integer', 'min:0', 'max:80'],
            'registration_value' => ['required', 'string', 'max:255'],
            'intro_video_type' => ['nullable', 'in:none,url,file'],
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

        if (($validated['intro_video_type'] ?? 'none') === 'file') {
            $introVideoFile = $request->file('intro_video_file');
        } elseif (($validated['intro_video_type'] ?? 'none') === 'url') {
            $payload['intro_video'] = $validated['intro_video_url'] ?? null;
        } else {
            $payload['intro_video'] = null;
        }

        try {
            $this->expertApplicationService->submitApprovedByAdmin(
                $request->user(),
                $user,
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
            ->with('success', $user->name.' is now an expert.');
    }

    public function allTypes(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(25);

        return view('pages.admin.users.all-types', [
            'title' => 'All types users',
            'users' => $users,
        ]);
    }

    public function makeAdmin(User $user): RedirectResponse
    {
        if ($user->user_type === User::USER_TYPE_ADMIN) {
            return redirect()
                ->route('admin.all-users.index')
                ->with('info', $user->name.' is already an admin.');
        }

        $user->update(['user_type' => User::USER_TYPE_ADMIN]);

        return redirect()
            ->route('admin.all-users.index')
            ->with('success', $user->name.' is now an admin.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->user_type !== User::USER_TYPE_USER) {
            return redirect()
                ->route('admin.users.index')
                ->with('danger', 'Only standard users can be deleted from this list.');
        }

        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.users.index')
                ->with('danger', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('danger', $name.' deleted.');
    }

    public function destroyAny(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.all-users.index')
                ->with('danger', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.all-users.index')
            ->with('danger', $name.' deleted.');
    }
}
