<div
    x-data
    class="pointer-events-none fixed top-4 right-4 z-[1000001] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2 sm:right-6 sm:w-full"
    aria-live="polite"
>
    <template x-for="item in $store.toast.items" :key="item.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-4 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="translate-x-4 opacity-0"
            class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-theme-sm dark:shadow-none"
            :class="{
                'border-green-200 bg-green-50 text-green-800 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-400':
                    item.type === 'success',
                'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400':
                    item.type === 'error' || item.type === 'danger',
                'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200':
                    item.type === 'warning',
                'border-brand-200 bg-brand-50 text-brand-800 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-200':
                    item.type === 'info',
            }"
        >
            <p class="min-w-0 flex-1 text-sm font-medium leading-snug" x-text="item.message"></p>
            <button
                type="button"
                class="shrink-0 rounded-lg p-1 opacity-70 hover:opacity-100 focus:outline-hidden focus:ring-2 focus:ring-brand-500/30"
                @click="$store.toast.dismiss(item.id)"
            >
                <span class="sr-only">Dismiss</span>
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
        </div>
    </template>
</div>

@if (session()->has('success') ||
    session()->has('error') ||
    session()->has('danger') ||
    session()->has('warning') ||
    session()->has('info') ||
    session()->has('status'))
    <div
        x-data
        x-init="$nextTick(() => {
            @if (session()->has('success'))
                $store.toast.show(@js(session('success')), 'success');
            @endif
            @if (session()->has('status'))
                $store.toast.show(@js(session('status')), 'success');
            @endif
            @if (session()->has('error'))
                $store.toast.show(@js(session('error')), 'error');
            @endif
            @if (session()->has('danger'))
                $store.toast.show(@js(session('danger')), 'danger');
            @endif
            @if (session()->has('warning'))
                $store.toast.show(@js(session('warning')), 'warning');
            @endif
            @if (session()->has('info'))
                $store.toast.show(@js(session('info')), 'info');
            @endif
        })"
        class="hidden"
        aria-hidden="true"
    ></div>
@endif
