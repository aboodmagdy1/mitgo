@php
    use App\Support\DashboardDateFilter;
    use Carbon\Carbon;
    $filter = DashboardDateFilter::getCurrentFilter();
    $presetRanges = [
        'last_week' => [Carbon::now()->subWeek()->format('Y-m-d'), Carbon::now()->format('Y-m-d')],
        'last_month' => [Carbon::now()->subMonth()->format('Y-m-d'), Carbon::now()->format('Y-m-d')],
        'last_3_months' => [Carbon::now()->subMonths(3)->format('Y-m-d'), Carbon::now()->format('Y-m-d')],
        'last_year' => [Carbon::now()->subYear()->format('Y-m-d'), Carbon::now()->format('Y-m-d')],
    ];
@endphp

<header class="fi-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
        <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
            {{ $heading }}
        </h1>

        {{-- Date filter: wire:ignore prevents Livewire from re-rendering and breaking Alpine state --}}
        <div
            wire:ignore
            x-data="{
                preset: @js($filter['preset']),
                dateFrom: @js($filter['date_from'] ?? $presetRanges['last_month'][0]),
                dateTo: @js($filter['date_to'] ?? $presetRanges['last_month'][1]),
                presetRanges: @js($presetRanges),
                applyFilter() {
                    if (this.preset === 'custom' && (!this.dateFrom || !this.dateTo)) return;
                    const payload = {
                        preset: this.preset,
                        date_from: this.dateFrom || null,
                        date_to: this.dateTo || null
                    };
                    $wire.applyDateFilter(payload);
                },
                onPresetChange() {
                    if (this.preset === 'custom') {
                        this.dateFrom = this.dateFrom || this.presetRanges['last_month'][0];
                        this.dateTo = this.dateTo || this.presetRanges['last_month'][1];
                        return;
                    }
                    if (this.preset === 'none') {
                        this.applyFilter();
                        return;
                    }
                    if (this.presetRanges[this.preset]) {
                        this.dateFrom = this.presetRanges[this.preset][0];
                        this.dateTo = this.presetRanges[this.preset][1];
                        this.applyFilter();
                    }
                }
            }"
            class="flex items-center gap-2"
        >
            <label class="shrink-0 text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ 'الفترة' }}:
            </label>
            <select
                x-model="preset"
                x-on:change="onPresetChange()"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-950/5 transition hover:bg-gray-50 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10 dark:focus:border-primary-500"
            >
                <option value="none">{{ 'بدون فلتر' }}</option>
                <option value="last_week">{{ 'آخر أسبوع' }}</option>
                <option value="last_month">{{ 'آخر شهر' }}</option>
                <option value="last_3_months">{{ 'آخر 3 أشهر' }}</option>
                <option value="last_year">{{ 'آخر سنة' }}</option>
                <option value="custom">{{ 'مخصص' }}</option>
            </select>

            <template x-if="preset === 'custom'">
                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50/80 px-3 py-2 dark:border-gray-700 dark:bg-gray-800/50">
                    <input
                        type="date"
                        x-model="dateFrom"
                        class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-white/5 dark:text-white"
                    />
                    <span class="text-gray-400">–</span>
                    <input
                        type="date"
                        x-model="dateTo"
                        class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-white/5 dark:text-white"
                    />
                    <button
                        type="button"
                        x-on:click="applyFilter()"
                        class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400"
                    >
                        {{ 'تطبيق' }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</header>
