<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    public string $dateFrom = '';

    public string $dateTo = '';

    protected $listeners = ['financial-filter-changed' => 'onFilterChanged'];

    public function onFilterChanged(string $dateFrom, string $dateTo): void
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
    }

    protected function getStats(): array
    {
        $range = $this->resolveRange();
        $data  = app(FinancialReportService::class)->getStatCards($range);

        return [
            Stat::make('إجمالي الإيرادات', $this->formatMoney($data['total_revenue']))
                ->description('من المدفوعات المكتملة')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->icon('heroicon-m-currency-dollar'),

            Stat::make('أرباح الشركة', $this->formatMoney($data['company_profit']))
                ->description('عمولة المنصة')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary')
                ->icon('heroicon-m-building-office'),

            Stat::make('أرباح السائقين', $this->formatMoney($data['driver_earnings']))
                ->description('صافي مدفوعات السائق')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info')
                ->icon('heroicon-m-truck'),

            Stat::make('متوسط تكلفة الرحلة', $this->formatMoney($data['avg_trip_fare']))
                ->description('لكل رحلة مكتملة')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning')
                ->icon('heroicon-m-calculator'),

            Stat::make('إجمالي الضرائب (15%)', $this->formatMoney($data['total_taxes']))
                ->description('15% من إجمالي الإيرادات')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('gray')
                ->icon('heroicon-m-receipt-percent'),

            Stat::make('خصومات الكوبونات', $this->formatMoney($data['coupon_discounts']))
                ->description('إجمالي الخصومات الممنوحة')
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning')
                ->icon('heroicon-m-tag'),

            Stat::make('رسوم الإلغاء', $this->formatMoney($data['cancellation_fees']))
                ->description('رسوم محصّلة')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->icon('heroicon-m-x-circle'),

            Stat::make('رسوم الانتظار', $this->formatMoney($data['waiting_fees']))
                ->description('رسوم محصّلة')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->icon('heroicon-m-clock'),

            Stat::make('المدفوعات المعلقة', number_format($data['pending_count']))
                ->description($this->formatMoney($data['pending_amount']) . ' معلق')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->icon('heroicon-m-exclamation-triangle'),

            Stat::make('المبالغ المستردة', $this->formatMoney($data['refunded_amount']))
                ->description('إجمالي الاسترداد المعالج')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger')
                ->icon('heroicon-m-arrow-uturn-left'),

            Stat::make('أرصدة المحافظ', $this->formatMoney($data['wallet_balance']))
                ->description('محافظ جميع المستخدمين والسائقين')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('info')
                ->icon('heroicon-m-wallet'),
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

    private function formatMoney(float $value): string
    {
        return 'SAR ' . number_format($value, 2);
    }
}
