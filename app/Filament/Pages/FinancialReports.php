<?php

namespace App\Filament\Pages;

use App\Filament\Pages\FinancialReports\Widgets\CommissionVsDriverChart;
use App\Filament\Pages\FinancialReports\Widgets\CouponImpactChart;
use App\Filament\Pages\FinancialReports\Widgets\DailySummaryTableWidget;
use App\Filament\Pages\FinancialReports\Widgets\FinancialStatsWidget;
use App\Filament\Pages\FinancialReports\Widgets\PaymentMethodsChart;
use App\Filament\Pages\FinancialReports\Widgets\RevenueByHourChart;
use App\Filament\Pages\FinancialReports\Widgets\RevenueByZoneChart;
use App\Filament\Pages\FinancialReports\Widgets\RevenueOverTimeChart;
use App\Filament\Pages\FinancialReports\Widgets\TopEarningDriversChart;
use App\Filament\Pages\FinancialReports\Widgets\TripsVsRevenueChart;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class FinancialReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.financial-reports';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->subMonth()->format('Y-m-d');
        $this->dateTo   = Carbon::now()->format('Y-m-d');
    }

    public function applyDateFilter(): void
    {
        $this->validate([
            'dateFrom' => 'required|date',
            'dateTo'   => 'required|date|after_or_equal:dateFrom',
        ]);

        app(FinancialReportService::class)->forgetCache($this->getDateRange());

        $this->dispatch('financial-filter-changed', dateFrom: $this->dateFrom, dateTo: $this->dateTo);
    }

    /**
     * @return array{Carbon,Carbon}|null
     */
    public function getDateRange(): ?array
    {
        if (empty($this->dateFrom) || empty($this->dateTo)) {
            return null;
        }

        return [
            Carbon::parse($this->dateFrom, config('app.timezone'))->startOfDay(),
            Carbon::parse($this->dateTo, config('app.timezone'))->endOfDay(),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    public static function getNavigationLabel(): string
    {
        return __('financial.nav_label');
    }

    public function getTitle(): string
    {
        return __('financial.page_title');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinancialStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RevenueOverTimeChart::class,
            PaymentMethodsChart::class,
            CommissionVsDriverChart::class,
            TopEarningDriversChart::class,
            CouponImpactChart::class,
            TripsVsRevenueChart::class,
            RevenueByZoneChart::class,
            RevenueByHourChart::class,
            DailySummaryTableWidget::class,
        ];
    }

    public function getHeaderWidgetsData(): array
    {
        return ['dateFrom' => $this->dateFrom, 'dateTo' => $this->dateTo];
    }

    public function getFooterWidgetsData(): array
    {
        return ['dateFrom' => $this->dateFrom, 'dateTo' => $this->dateTo];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.financial-reports-header', [
            'heading'  => $this->getTitle(),
            'dateFrom' => $this->dateFrom,
            'dateTo'   => $this->dateTo,
        ]);
    }
}
