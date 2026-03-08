<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PaymentMethodsChart extends ApexChartWidget
{
    protected static ?int $sort = 3;

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
        return __('financial.chart_payment_methods');
    }

    protected function getOptions(): array
    {
        $data = app(FinancialReportService::class)->getPaymentMethodsDistribution($this->resolveRange());

        return [
            'chart'  => ['type' => 'donut', 'height' => 300],
            'series' => $data['amounts'],
            'labels' => $data['labels'],
            'legend' => ['position' => 'bottom'],
            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899'],
            'dataLabels' => ['enabled' => true],
            'plotOptions' => [
                'pie' => [
                    'donut' => ['size' => '65%'],
                ],
            ],
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
