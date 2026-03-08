<header class="fi-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
        <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
            {{ $heading }}
        </h1>

        <div
            wire:ignore.self
            class="flex flex-wrap items-center gap-2"
        >
            <label class="shrink-0 text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ __('financial.date_range') }}:
            </label>

            <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50/80 px-3 py-2 dark:border-gray-700 dark:bg-gray-800/50">
                <input
                    type="date"
                    wire:model="dateFrom"
                    class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-white/5 dark:text-white"
                />
                <span class="text-gray-400">–</span>
                <input
                    type="date"
                    wire:model="dateTo"
                    class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-white/5 dark:text-white"
                />
                <button
                    type="button"
                    wire:click="applyDateFilter"
                    wire:loading.attr="disabled"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-60 dark:bg-primary-500 dark:hover:bg-primary-400"
                >
                    <span wire:loading.remove wire:target="applyDateFilter">{{ __('financial.apply_filter') }}</span>
                    <span wire:loading wire:target="applyDateFilter">{{ __('financial.loading') }}</span>
                </button>
            </div>
        </div>
    </div>
</header>
