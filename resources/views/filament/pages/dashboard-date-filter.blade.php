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

<div
    x-data="{
        preset: @js($filter['preset']),
        dateFrom: @js($filter['date_from'] ?? $presetRanges['last_month'][0]),
        dateTo: @js($filter['date_to'] ?? $presetRanges['last_month'][1]),
        presetRanges: @js($presetRanges),
        applyFilter() {
            $wire.applyDateFilter({
                preset: this.preset,
                date_from: this.dateFrom,
                date_to: this.dateTo
            });
        },
        onPresetChange() {
            if (this.preset === 'custom') return;
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
    class="flex flex-col gap-3"
>
    <div class="flex items-center gap-2">
        <label class="shrink-0 text-xs font-medium text-gray-600 dark:text-gray-400">
            {{ __('dashboard.filter_preset') }}:
        </label>
        <select
            x-model="preset"
            x-on:change="onPresetChange()"
            class="w-36 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-950 shadow-sm transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-white/5 dark:text-white dark:focus:border-primary-500"
        >
            <option value="none">{{ __('dashboard.filter_none') }}</option>
            <option value="last_week">{{ __('dashboard.filter_last_week') }}</option>
            <option value="last_month">{{ __('dashboard.filter_last_month') }}</option>
            <option value="last_3_months">{{ __('dashboard.filter_last_3_months') }}</option>
            <option value="last_year">{{ __('dashboard.filter_last_year') }}</option>
            <option value="custom">{{ __('dashboard.filter_custom') }}</option>
        </select>
    </div>

    <template x-if="preset === 'custom'">
        <div class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50/50 p-3 dark:border-gray-700 dark:bg-gray-800/50">
            <div class="min-w-[130px]">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ __('dashboard.filter_date_from') }}
                </label>
                <input
                    type="date"
                    x-model="dateFrom"
                    class="block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-white/5 dark:text-white"
                />
            </div>
            <div class="min-w-[130px]">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ __('dashboard.filter_date_to') }}
                </label>
                <input
                    type="date"
                    x-model="dateTo"
                    class="block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-white/5 dark:text-white"
                />
            </div>
            <button
                type="button"
                x-on:click="applyFilter()"
                class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400"
            >
                {{ __('dashboard.filter_apply') }}
            </button>
        </div>
    </template>
</div>
