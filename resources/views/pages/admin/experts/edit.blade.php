@extends('layouts.app')

@php
    $input =
        'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $select =
        'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pr-11 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $textarea =
        'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 min-h-[100px] w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';

    $defaultEducation = [['institution' => '', 'degree' => '', 'year' => '']];
    $defaultExperience = [['title' => '', 'organization' => '', 'start_year' => '', 'end_year' => '', 'description' => '']];
    $defaultPortfolio = [['title' => '', 'url' => '']];
    $defaultDocuments = [['name' => '']];

    $existingEducation = collect($detail->education ?? [])
        ->map(fn ($row) => [
            'institution' => $row['institution'] ?? '',
            'degree' => $row['degree'] ?? '',
            'year' => $row['year'] ?? '',
        ])
        ->values()
        ->all() ?: $defaultEducation;

    $existingExperience = collect($detail->experience ?? [])
        ->map(fn ($row) => [
            'title' => $row['title'] ?? '',
            'organization' => $row['organization'] ?? '',
            'start_year' => $row['start_year'] ?? '',
            'end_year' => $row['end_year'] ?? '',
            'description' => $row['description'] ?? '',
        ])
        ->values()
        ->all() ?: $defaultExperience;

    $existingPortfolio = collect($detail->portfolio ?? [])
        ->map(fn ($row) => [
            'title' => $row['title'] ?? '',
            'url' => $row['url'] ?? '',
        ])
        ->values()
        ->all() ?: $defaultPortfolio;

    $formState = [
        'categories' => $categoriesPayload,
        'categoryId' => (string) old('category_id', $detail->category_id),
        'subcategoryId' => (string) old('subcategory_id', $detail->subcategory_id),
        'skillIds' => array_map('strval', old('skill_ids', $detail->skills->pluck('id')->all())),
        'introVideoType' => old('intro_video_type', $introVideoType),
        'education' => old('education', $existingEducation),
        'experience' => old('experience', $existingExperience),
        'portfolio' => old('portfolio', $existingPortfolio),
        'documents' => collect(old('documents', $defaultDocuments))
            ->map(fn ($d) => ['name' => $d['name'] ?? ''])
            ->values()
            ->all(),
    ];

    $statusValue = old(
        'status',
        $detail->status instanceof \BackedEnum ? $detail->status->value : $detail->status
    );
@endphp

@section('content')
    <div x-data="{
        ...@js($formState),
        get subcategories() {
            const cat = this.categories.find(c => String(c.id) === String(this.categoryId));
            return cat ? cat.subcategories : [];
        },
        get skills() {
            const sub = this.subcategories.find(s => String(s.id) === String(this.subcategoryId));
            return sub ? sub.skills : [];
        },
        onCategoryChange() {
            this.subcategoryId = '';
            this.skillIds = [];
        },
        onSubcategoryChange() {
            this.skillIds = [];
        },
        addEducation() { this.education.push({ institution: '', degree: '', year: '' }); },
        removeEducation(i) { this.education.splice(i, 1); },
        addExperience() { this.experience.push({ title: '', organization: '', start_year: '', end_year: '', description: '' }); },
        removeExperience(i) { this.experience.splice(i, 1); },
        addPortfolio() { this.portfolio.push({ title: '', url: '' }); },
        removePortfolio(i) { this.portfolio.splice(i, 1); },
        addDocument() { this.documents.push({ name: '' }); },
        removeDocument(i) { this.documents.splice(i, 1); }
    }">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-common.page-breadcrumb pageTitle="Edit expert" />
            <a href="{{ route('admin.experts.show', $expert) }}" class="text-sm font-medium text-brand-500 hover:text-brand-600">
                ← Back to expert
            </a>
        </div>

        <div class="mb-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Update expert profile details for
                <span class="font-medium text-gray-800 dark:text-white/90">{{ $expert->name }}</span>
                ({{ $expert->email }}). Expert code
                <span class="font-medium text-gray-800 dark:text-white/90">{{ $detail->expert_code }}</span>
                stays the same.
            </p>
        </div>

        @if ($errors->any())
            <div
                class="mb-6 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.experts.update', $expert) }}"
            enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <x-common.component-card title="Status">
                <div>
                    <label class="{{ $label }}">Profile status <span class="text-red-500">*</span></label>
                    <select name="status" required class="{{ $select }}">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($statusValue === $status->value)>
                                {{ ucfirst($status->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </x-common.component-card>

            <x-common.component-card title="Taxonomy">
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" x-model="categoryId" @change="onCategoryChange()" required
                            class="{{ $select }}">
                            <option value="">Select category</option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name"
                                    :selected="String(cat.id) === String(categoryId)"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Subcategory <span class="text-red-500">*</span></label>
                        <select name="subcategory_id" x-model="subcategoryId" @change="onSubcategoryChange()" required
                            class="{{ $select }}" :disabled="!categoryId">
                            <option value="">Select subcategory</option>
                            <template x-for="sub in subcategories" :key="sub.id">
                                <option :value="sub.id" x-text="sub.name"
                                    :selected="String(sub.id) === String(subcategoryId)"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="mt-5">
                    <label class="{{ $label }}">Skills <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3"
                        x-show="skills.length > 0">
                        <template x-for="skill in skills" :key="skill.id">
                            <label
                                class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-800">
                                <input type="checkbox" name="skill_ids[]" :value="skill.id"
                                    x-model="skillIds"
                                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20">
                                <span class="text-gray-800 dark:text-white/90" x-text="skill.name"></span>
                            </label>
                        </template>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="!subcategoryId">
                        Select a subcategory to choose skills.
                    </p>
                </div>
            </x-common.component-card>

            <x-common.component-card title="Profile">
                <div class="space-y-5">
                    <div>
                        <label class="{{ $label }}">Professional headline <span class="text-red-500">*</span></label>
                        <input type="text" name="professional_headline"
                            value="{{ old('professional_headline', $detail->professional_headline) }}"
                            required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Bio <span class="text-red-500">*</span></label>
                        <textarea name="bio" required class="{{ $textarea }}">{{ old('bio', $detail->bio) }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div>
                            <label class="{{ $label }}">Years of experience <span class="text-red-500">*</span></label>
                            <input type="number" min="0" max="80" name="years_of_experience"
                                value="{{ old('years_of_experience', $detail->years_of_experience) }}" required
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Registration value <span class="text-red-500">*</span></label>
                            <input type="text" name="registration_value"
                                value="{{ old('registration_value', $detail->registration_value) }}"
                                required class="{{ $input }}">
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Languages <span class="text-red-500">*</span></label>
                        <input type="text" name="languages"
                            value="{{ is_array(old('languages')) ? implode(', ', old('languages')) : old('languages', implode(', ', $detail->languages ?? [])) }}"
                            required placeholder="en, bn" class="{{ $input }}">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Comma-separated language codes.</p>
                    </div>
                    <div>
                        <label class="{{ $label }}">Avatar</label>
                        @if ($detail->avatarUrl())
                            <div class="mb-3">
                                <img src="{{ $detail->avatarUrl() }}" alt="Current avatar"
                                    class="h-16 w-16 rounded-full object-cover">
                            </div>
                        @endif
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                            class="block w-full text-sm text-gray-600 dark:text-gray-300">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty to keep the current avatar.</p>
                    </div>
                </div>
            </x-common.component-card>

            <x-common.component-card title="Intro video">
                <div class="mb-4 flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="radio" name="intro_video_type" value="none" x-model="introVideoType"
                            class="text-brand-500 focus:ring-brand-500/20">
                        None
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="radio" name="intro_video_type" value="url" x-model="introVideoType"
                            class="text-brand-500 focus:ring-brand-500/20">
                        URL
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="radio" name="intro_video_type" value="file" x-model="introVideoType"
                            class="text-brand-500 focus:ring-brand-500/20">
                        Upload new file
                    </label>
                    @if ($detail->intro_video && ! str_starts_with((string) $detail->intro_video, 'http://') && ! str_starts_with((string) $detail->intro_video, 'https://'))
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="radio" name="intro_video_type" value="keep" x-model="introVideoType"
                                class="text-brand-500 focus:ring-brand-500/20">
                            Keep current file
                        </label>
                    @endif
                </div>
                <div x-show="introVideoType === 'url'">
                    <label class="{{ $label }}">Video URL</label>
                    <input type="url" name="intro_video_url"
                        value="{{ old('intro_video_url', $introVideoUrl) }}"
                        placeholder="https://..." class="{{ $input }}">
                </div>
                <div x-show="introVideoType === 'file'">
                    <label class="{{ $label }}">Video file</label>
                    <input type="file" name="intro_video_file" accept="video/mp4,video/webm,video/quicktime"
                        class="block w-full text-sm text-gray-600 dark:text-gray-300">
                </div>
                @if ($detail->introVideoUrl() && ! str_starts_with((string) $detail->intro_video, 'http'))
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-show="introVideoType === 'keep'">
                        Current file will be kept.
                        <a href="{{ $detail->introVideoUrl() }}" target="_blank" class="text-brand-500 hover:underline">Open</a>
                    </p>
                @endif
            </x-common.component-card>

            <x-common.component-card title="Documents">
                @if (($detail->documents ?? []) !== [])
                    <div class="mb-4 space-y-2">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Current documents (replaced only if you upload new ones):</p>
                        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
                            @foreach ($detail->documentsWithUrls() as $doc)
                                <li>
                                    {{ $doc['name'] ?: 'Document' }}
                                    @if ($doc['url'])
                                        — <a href="{{ $doc['url'] }}" target="_blank" class="text-brand-500 hover:underline">Open</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="space-y-4">
                    <template x-for="(doc, index) in documents" :key="index">
                        <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800 lg:grid-cols-12">
                            <div class="lg:col-span-5">
                                <label class="{{ $label }}">Document name</label>
                                <input type="text" :name="`documents[${index}][name]`" x-model="doc.name"
                                    class="{{ $input }}">
                            </div>
                            <div class="lg:col-span-5">
                                <label class="{{ $label }}">File</label>
                                <input type="file" :name="`documents[${index}][file]`"
                                    accept=".pdf,.doc,.docx,image/jpeg,image/png,image/webp"
                                    class="block w-full text-sm text-gray-600 dark:text-gray-300">
                            </div>
                            <div class="flex items-end lg:col-span-2">
                                <button type="button" @click="removeDocument(index)"
                                    class="text-sm font-medium text-red-600 hover:text-red-700"
                                    x-show="documents.length > 1">Remove</button>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addDocument()"
                        class="text-sm font-medium text-brand-500 hover:text-brand-600">+ Add document</button>
                </div>
            </x-common.component-card>

            <x-common.component-card title="Education">
                <div class="space-y-4">
                    <template x-for="(row, index) in education" :key="index">
                        <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800 lg:grid-cols-12">
                            <div class="lg:col-span-4">
                                <label class="{{ $label }}">Institution</label>
                                <input type="text" :name="`education[${index}][institution]`" x-model="row.institution"
                                    class="{{ $input }}">
                            </div>
                            <div class="lg:col-span-4">
                                <label class="{{ $label }}">Degree</label>
                                <input type="text" :name="`education[${index}][degree]`" x-model="row.degree"
                                    class="{{ $input }}">
                            </div>
                            <div class="lg:col-span-3">
                                <label class="{{ $label }}">Year</label>
                                <input type="number" :name="`education[${index}][year]`" x-model="row.year"
                                    class="{{ $input }}">
                            </div>
                            <div class="flex items-end lg:col-span-1">
                                <button type="button" @click="removeEducation(index)"
                                    class="text-sm font-medium text-red-600 hover:text-red-700"
                                    x-show="education.length > 1">Remove</button>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addEducation()"
                        class="text-sm font-medium text-brand-500 hover:text-brand-600">+ Add education</button>
                </div>
            </x-common.component-card>

            <x-common.component-card title="Experience">
                <div class="space-y-4">
                    <template x-for="(row, index) in experience" :key="index">
                        <div class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                <div>
                                    <label class="{{ $label }}">Title</label>
                                    <input type="text" :name="`experience[${index}][title]`" x-model="row.title"
                                        class="{{ $input }}">
                                </div>
                                <div>
                                    <label class="{{ $label }}">Organization</label>
                                    <input type="text" :name="`experience[${index}][organization]`"
                                        x-model="row.organization" class="{{ $input }}">
                                </div>
                                <div>
                                    <label class="{{ $label }}">Start year</label>
                                    <input type="number" :name="`experience[${index}][start_year]`"
                                        x-model="row.start_year" class="{{ $input }}">
                                </div>
                                <div>
                                    <label class="{{ $label }}">End year</label>
                                    <input type="number" :name="`experience[${index}][end_year]`"
                                        x-model="row.end_year" class="{{ $input }}">
                                </div>
                            </div>
                            <div>
                                <label class="{{ $label }}">Description</label>
                                <textarea :name="`experience[${index}][description]`" x-model="row.description"
                                    class="{{ $textarea }}"></textarea>
                            </div>
                            <button type="button" @click="removeExperience(index)"
                                class="text-sm font-medium text-red-600 hover:text-red-700"
                                x-show="experience.length > 1">Remove</button>
                        </div>
                    </template>
                    <button type="button" @click="addExperience()"
                        class="text-sm font-medium text-brand-500 hover:text-brand-600">+ Add experience</button>
                </div>
            </x-common.component-card>

            <x-common.component-card title="Portfolio">
                <div class="space-y-4">
                    <template x-for="(row, index) in portfolio" :key="index">
                        <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800 lg:grid-cols-12">
                            <div class="lg:col-span-4">
                                <label class="{{ $label }}">Title</label>
                                <input type="text" :name="`portfolio[${index}][title]`" x-model="row.title"
                                    class="{{ $input }}">
                            </div>
                            <div class="lg:col-span-7">
                                <label class="{{ $label }}">URL</label>
                                <input type="url" :name="`portfolio[${index}][url]`" x-model="row.url"
                                    class="{{ $input }}">
                            </div>
                            <div class="flex items-end lg:col-span-1">
                                <button type="button" @click="removePortfolio(index)"
                                    class="text-sm font-medium text-red-600 hover:text-red-700"
                                    x-show="portfolio.length > 1">Remove</button>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addPortfolio()"
                        class="text-sm font-medium text-brand-500 hover:text-brand-600">+ Add portfolio item</button>
                </div>
            </x-common.component-card>

            <div class="flex flex-wrap gap-3">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">
                    Save expert details
                </button>
                <a href="{{ route('admin.experts.show', $expert) }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
