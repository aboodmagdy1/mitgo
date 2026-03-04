<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Settings\GeneralSettings;
use App\Support\DashboardDateFilter;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class RevenueBreakdownWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.revenue-breakdown';

    protected static int $cacheTtlSeconds = 300;

    protected $listeners = ['dashboard-filter-changed' => '$refresh'];

    /**
     * Defer loading until visible; improves initial page load.
     */
    protected static bool $isLazy = true;

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function dateScope(Builder $query): Builder
    {
        $range = DashboardDateFilter::getDateRange();

        if ($range === null) {
            return $query;
        }

        return $query->whereBetween('created_at', $range);
    }

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
        // Revenue = when trip completed (ended_at), not when created
        $query = Trip::query()->where('status', \App\Enums\TripStatus::COMPLETED->value);
        $range = DashboardDateFilter::getDateRange();
        if ($range !== null) {
            $query->whereBetween('ended_at', $range);
        }
        $totalRevenue = $query->sum('actual_fare') ?? 0;

        $commissionRate = app(GeneralSettings::class)->commission_rate / 100;
        $taxRate = 0.15;
        $tax = $totalRevenue * $taxRate;
        $total_without_tax = $totalRevenue - $tax;
        $companyProfit = $total_without_tax * $commissionRate;
        $driverProfit = $total_without_tax - $companyProfit;

        return [
            'companyProfit' => $companyProfit,
            'tax' => $tax,
            'driverProfit' => $driverProfit,
            'totalRevenue' => $totalRevenue,
        ];
    }
}
