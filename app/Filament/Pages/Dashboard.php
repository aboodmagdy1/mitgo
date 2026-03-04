<?php

namespace App\Filament\Pages;

use App\Support\DashboardDateFilter;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class Dashboard extends BaseDashboard
{
    public $filterVersion = 0;

    public function applyDateFilter(array $data): void
    {
        DashboardDateFilter::apply($data);
        $this->filterVersion++;

        // Invalidate dashboard cache so widgets fetch fresh data for the new filter
        $suffix = DashboardDateFilter::getCacheKeySuffix();
        $keys = ['dashboard:advanced_stats:' . $suffix, 'dashboard:revenue:' . $suffix];
        foreach ($keys as $key) {
            try {
                Cache::store('redis')->forget($key);
            } catch (\Throwable) {
                // Redis not available
            }
            Cache::forget($key);
        }

        // Force RevenueBreakdownWidget to re-render with fresh data
        $this->dispatch('dashboard-filter-changed');
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.dashboard-header', [
            'heading' => $this->getHeading(),
        ]);
    }

    public function getWidgetData(): array
    {
        return array_merge(parent::getWidgetData(), [
            'filterVersion' => $this->filterVersion,
        ]);
    }
}
