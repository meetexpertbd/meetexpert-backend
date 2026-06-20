@extends('layouts.app')

@php
    $input =
        'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
    $editHasErrors = $errors->any() && old('_form') === 'edit';
    $createHasErrors = $errors->any() && old('_form') === 'create';
@endphp

@section('content')
    <div x-data='{
        createOpen: @json($createHasErrors),
        editOpen: @json($editHasErrors),
        editAction: @json($editHasErrors && old('category_id') ? route('taxonomy.categories.update', old('category_id')) : ''),
        openEdit(btn) {
            this.editAction = btn.dataset.updateUrl;
            this.$refs.editName.value = btn.dataset.name;
            this.$refs.editDescription.value = btn.dataset.description ?? "";
            this.$refs.editSortOrder.value = btn.dataset.sortOrder ?? "0";
            this.$refs.editActive.checked = btn.dataset.active === "1";
            this.$refs.editCategoryId.value = btn.dataset.categoryId;
            this.editOpen = true;
        },
        async confirmDelete(e, name) {
            const form = e.target.closest("form");
            if (!form || !window.Swal) return;
            const { isConfirmed } = await window.Swal.fire({
                title: "Delete category?",
                text: name
                    ? "“" + name + "” will be removed permanently."
                    : "This category will be removed permanently.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete",
                cancelButtonText: "Cancel",
                reverseButtons: true,
                focusCancel: true,
                confirmButtonColor: "#dc2626",
                cancelButtonColor: "#6b7280",
            });
            if (isConfirmed) form.submit();
        }
    }' @keydown.escape.window="createOpen = false; editOpen = false">
        <x-common.page-breadcrumb pageTitle="Categories" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage taxonomy categories.</p>
            <button type="button" @click="createOpen = true"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">
                Add category
            </button>
        </div>

        <x-common.component-card title="All categories">
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/[0.03]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Name
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Slug
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Order
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Active
                            </th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-white/[0.02]">
                        @forelse ($categories as $category)
                            <tr>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">
                                    {{ $category->name }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $category->slug }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $category->sort_order }}
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    @if ($category->is_active)
                                        <span
                                            class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-500/15 dark:text-green-400">Yes</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">No</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right text-sm">
                                    <button type="button"
                                        class="text-brand-500 hover:text-brand-600"
                                        data-update-url="{{ route('taxonomy.categories.update', $category) }}"
                                        data-category-id="{{ $category->id }}"
                                        data-name="{{ e($category->name) }}"
                                        data-description="{{ e(str_replace(["\r\n", "\r", "\n"], ' ', $category->description ?? '')) }}"
                                        data-sort-order="{{ $category->sort_order }}"
                                        data-active="{{ $category->is_active ? '1' : '0' }}"
                                        @click="openEdit($event.currentTarget)">
                                        Edit
                                    </button>
                                    <form action="{{ route('taxonomy.categories.destroy', $category) }}" method="post"
                                        class="ml-3 inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                                            @click="confirmDelete($event, @js($category->name))">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No categories yet.
                                    <button type="button" @click="createOpen = true" class="text-brand-500 hover:underline">
                                        Create one
                                    </button>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="mt-6">
                    {{ $categories->links() }}
                </div>
            @endif
        </x-common.component-card>

        {{-- Create modal --}}
        <div x-show="createOpen" x-cloak
            class="fixed inset-0 z-[999999] flex items-center justify-center overflow-y-auto p-5">
            <div @click="createOpen = false"
                class="fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
            <div @click.stop
                class="relative w-full max-w-lg rounded-3xl bg-white p-6 dark:bg-gray-900 sm:p-10"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">
                <button type="button" @click="createOpen = false"
                    class="absolute right-3 top-3 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:right-6 sm:top-6 sm:h-11 sm:w-11">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"
                            fill="currentColor" />
                    </svg>
                </button>
                <h3 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">New category</h3>
                <form action="{{ route('taxonomy.categories.store') }}" method="post" class="space-y-5">
                    @csrf
                    <input type="hidden" name="_form" value="create">

                    <div>
                        <label for="create_name" class="{{ $label }}">Name <span class="text-red-500">*</span></label>
                        <input id="create_name" name="name" type="text" value="{{ old('_form') === 'create' ? old('name') : '' }}"
                            required class="{{ $input }} @error('name') border-red-500 @enderror" />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="create_description" class="{{ $label }}">Description</label>
                        <textarea id="create_description" name="description" rows="4"
                            class="{{ str_replace('h-11', 'min-h-[120px]', $input) }} @error('description') border-red-500 @enderror">{{ old('_form') === 'create' ? old('description') : '' }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="create_sort_order" class="{{ $label }}">Sort order</label>
                        <input id="create_sort_order" name="sort_order" type="number" min="0"
                            value="{{ old('_form') === 'create' ? old('sort_order', 0) : 0 }}"
                            class="{{ $input }} @error('sort_order') border-red-500 @enderror" />
                        @error('sort_order')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input id="create_is_active" name="is_active" type="checkbox" value="1"
                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900"
                            @checked(old('_form') === 'create' ? (bool) old('is_active', true) : true)>
                        <label for="create_is_active" class="text-sm text-gray-700 dark:text-gray-400">Active</label>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">
                            Save
                        </button>
                        <button type="button" @click="createOpen = false"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit modal --}}
        <div x-show="editOpen" x-cloak
            class="fixed inset-0 z-[999999] flex items-center justify-center overflow-y-auto p-5">
            <div @click="editOpen = false"
                class="fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
            <div @click.stop
                class="relative w-full max-w-lg rounded-3xl bg-white p-6 dark:bg-gray-900 sm:p-10"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">
                <button type="button" @click="editOpen = false"
                    class="absolute right-3 top-3 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white sm:right-6 sm:top-6 sm:h-11 sm:w-11">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"
                            fill="currentColor" />
                    </svg>
                </button>
                <h3 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Edit category</h3>
                <form method="post" :action="editAction" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="category_id" x-ref="editCategoryId"
                        value="{{ old('_form') === 'edit' ? old('category_id') : '' }}">

                    <div>
                        <label for="edit_name" class="{{ $label }}">Name <span class="text-red-500">*</span></label>
                        <input id="edit_name" name="name" type="text" x-ref="editName"
                            value="{{ $editHasErrors ? old('name') : '' }}" required
                            class="{{ $input }} @error('name') border-red-500 @enderror" />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit_description" class="{{ $label }}">Description</label>
                        <textarea id="edit_description" name="description" rows="4" x-ref="editDescription"
                            class="{{ str_replace('h-11', 'min-h-[120px]', $input) }} @error('description') border-red-500 @enderror">{{ $editHasErrors ? old('description') : '' }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit_sort_order" class="{{ $label }}">Sort order</label>
                        <input id="edit_sort_order" name="sort_order" type="number" min="0" x-ref="editSortOrder"
                            value="{{ $editHasErrors ? old('sort_order', 0) : '' }}"
                            class="{{ $input }} @error('sort_order') border-red-500 @enderror" />
                        @error('sort_order')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input id="edit_is_active" name="is_active" type="checkbox" value="1" x-ref="editActive"
                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900"
                            @if ($editHasErrors) @checked(old('is_active')) @endif>
                        <label for="edit_is_active" class="text-sm text-gray-700 dark:text-gray-400">Active</label>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">
                            Update
                        </button>
                        <button type="button" @click="editOpen = false"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    @endpush
@endsection
