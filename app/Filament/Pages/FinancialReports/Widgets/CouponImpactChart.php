<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CouponImpactChart extends ApexChartWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected static bool $isLazy = true;

    public string $dateFrom = '';

    public string $dateTo = '';

    protected $listeners = ['financial-filter-changed' => 'onFilterChanged'];

    public function onFilterChanged(string $dateFrom, string $dateTo): void
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
        $this->updateOptions();
    }

    protected function getHeading(): ?string
    {
        return __('financial.chart_coupon_impact');
    }

    protected function getOptions(): array
    {
        $data = app(FinancialReportService::class)->getCouponImpact($this->resolveRange());

        if (empty($data['labels'])) {
            return [
                'chart'  => ['type' => 'bar', 'height' => 300],
                'series' => [['name' => __('financial.discount'), 'data' => [0]]],
                'xaxis'  => ['categories' => [__('financial.no_coupon_data')]],
                'colors' => ['#f59e0b'],
                'dataLabels' => ['enabled' => false],
            ];
        }

        return [
            'chart' => [
                'type'    => 'bar',
                'height'  => 300,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                ['name' => __('financial.discount'), 'data' => $data['discounts']],
                ['name' => __('financial.uses'),     'data' => $data['counts']],
            ],
            'xaxis'       => ['categories' => $data['labels']],
            'colors'      => ['#f59e0b', '#8b5cf6'],
            'legend'      => ['position' => 'top'],
            'dataLabels'  => ['enabled' => false],
            'plotOptions' => ['bar' => ['borderRadius' => 4]],
        ];
    }

    /** @return array{Carbon,Carbon}|null */
    private function resolveRange(): ?array
    {
        if (empty($this->dateFrom) || empty($this->dateTo)) {
            return null;
        }

        return [
            Carbon::parse($this->dateFrom)->startOfDay(),
            Carbon::parse($this->dateTo)->endOfDay(),
        ];
    }
}
