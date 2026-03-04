@php
    use App\Support\DashboardDateFilter;
    $currency = __('SAR');
    $formatAmount = fn($amount) => number_format($amount, 2);
    $periodLabel = DashboardDateFilter::hasActiveFilter() ? __('stats.revenue_in_period', ['amount' => $formatAmount($totalRevenue)]) : __('stats.revenue_all_time', ['amount' => $formatAmount($totalRevenue)]);
    $items = [
        ['label' => __('stats.revenue_company_profit'), 'value' => $companyProfit, 'icon' => 'heroicon-m-building-office-2', 'iconBg' => 'bg-primary-100 dark:bg-primary-900/30', 'iconColor' => 'text-primary-600 dark:text-primary-400'],
        ['label' => __('stats.revenue_tax'), 'value' => $tax, 'icon' => 'heroicon-m-calculator', 'iconBg' => 'bg-amber-100 dark:bg-amber-900/30', 'iconColor' => 'text-amber-600 dark:text-amber-400'],
        ['label' => __('stats.revenue_driver_profit'), 'value' => $driverProfit, 'icon' => 'heroicon-m-truck', 'iconBg' => 'bg-sky-100 dark:bg-sky-900/30', 'iconColor' => 'text-sky-600 dark:text-sky-400'],
    ];
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('stats.wallet_revenue')"
        :description="$periodLabel"
        icon="heroicon-m-currency-dollar"
        iconColor="success"
    >
        <div class="space-y-6 px-1 py-2">
            {{-- Revenue cards grid --}}
            <div class="grid gap-5 sm:grid-cols-3">
                @foreach($items as $item)
                    <div class="group relative overflow-hidden rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md dark:border-gray-700/80 dark:bg-gray-900/50">
                        <div class="flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ $item['label'] }}
                                </p>
                                <p class="mt-3 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                                    {{ $formatAmount($item['value']) }}
                                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ $currency }}</span>
                                </p>
                            </div>
                            <div class="ml-5 flex h-14 w-14 shrink-0 items-center justify-center rounded-xl {{ $item['iconBg'] }} {{ $item['iconColor'] }}">
                                <x-filament::icon :icon="$item['icon']" class="h-7 w-7" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Total revenue highlight --}}
            <div class="relative overflow-hidden rounded-2xl border-2 border-success-200 bg-gradient-to-br from-success-50 to-emerald-50 p-8 dark:border-success-800 dark:from-success-900/30 dark:to-emerald-900/20">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-success-500/20 text-success-600 dark:bg-success-500/30 dark:text-success-400">
                            <x-filament::icon icon="heroicon-m-banknotes" class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-success-700 dark:text-success-400">
                                {{ __('stats.revenue_total') }}
                            </p>
                            <p class="text-3xl font-bold tabular-nums text-success-800 dark:text-success-300 sm:text-4xl">
                                {{ $formatAmount($totalRevenue) }} {{ $currency }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
