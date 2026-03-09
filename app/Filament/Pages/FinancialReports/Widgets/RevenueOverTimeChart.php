<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueOverTimeChart extends ApexChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

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
        return 'الإيرادات عبر الزمن';
    }

    protected function getOptions(): array
    {
        $data = app(FinancialReportService::class)->getRevenueOverTime($this->resolveRange());

        return [
            'chart' => [
                'type'    => 'area',
                'height'  => 300,
                'toolbar' => ['show' => true],
            ],
            'series' => [
                ['name' => 'الإيرادات', 'data' => $data['data']],
            ],
            'xaxis' => [
                'categories' => $data['labels'],
                'labels'     => ['rotate' => -45],
            ],
            'stroke'      => ['curve' => 'smooth', 'width' => 2],
            'fill'        => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.4, 'opacityTo' => 0.05]],
            'colors'      => ['#10b981'],
            'dataLabels'  => ['enabled' => false],
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
