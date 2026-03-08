<?php

namespace App\Filament\Widgets;

use App\Services\FinancialReportService;
use App\Support\DashboardDateFilter;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class RevenueBreakdownWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.revenue-breakdown';

    protected static int $cacheTtlSeconds = 300;

    protected $listeners = ['dashboard-filter-changed' => '$refresh'];

    protected static bool $isLazy = true;

    protected function getViewData(): array
    {
        $cacheKey = 'dashboard:revenue:' . DashboardDateFilter::getCacheKeySuffix();

        try {
            return Cache::store('redis')->remember(
                $cacheKey,
                self::$cacheTtlSeconds,
                fn () => $this->computeRevenueData()
            );
        } catch (\Throwable) {
            return Cache::remember(
                $cacheKey,
                self::$cacheTtlSeconds,
                fn () => $this->computeRevenueData()
            );
        }
    }

    private function computeRevenueData(): array
    {
        $range = DashboardDateFilter::getDateRange();
        $stats = app(FinancialReportService::class)->getStatCards($range);

        return [
            'totalRevenue' => (float) ($stats['total_revenue'] ?? 0),
            'companyProfit' => (float) ($stats['company_profit'] ?? 0),
            'tax' => (float) ($stats['total_taxes'] ?? 0),
            'driverProfit' => (float) ($stats['driver_earnings'] ?? 0),
        ];
    }
}
