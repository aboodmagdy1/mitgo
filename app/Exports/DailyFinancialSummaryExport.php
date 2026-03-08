<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyFinancialSummaryExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    /** @param  array{Carbon,Carbon}|null  $dateRange */
    public function __construct(private readonly ?array $dateRange) {}

    public function collection(): Collection
    {
        return app(FinancialReportService::class)->getDailySummary($this->dateRange);
    }

    public function headings(): array
    {
        return [
            __('financial.col_date'),
            __('financial.col_total_trips'),
            __('financial.col_total_revenue'),
            __('financial.col_commission'),
            __('financial.col_driver_earnings'),
            __('financial.col_coupon_discounts'),
            __('financial.col_cancellation_fees'),
            __('financial.col_waiting_fees'),
            __('financial.col_net_revenue'),
        ];
    }

    public function map($row): array
    {
        return [
            $row->date,
            $row->total_trips,
            number_format((float) $row->total_revenue, 2),
            number_format((float) $row->commission, 2),
            number_format((float) $row->driver_earnings, 2),
            number_format((float) $row->coupon_discounts, 2),
            number_format((float) $row->cancellation_fees, 2),
            number_format((float) $row->waiting_fees, 2),
            number_format((float) $row->net_revenue, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return __('financial.export_sheet_title');
    }
}
