<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ 'التقارير المالية' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1f2937; background: #fff; padding: 24px; }
        h1 { font-size: 18px; font-weight: 700; color: #065f46; margin-bottom: 4px; }
        .subtitle { font-size: 11px; color: #6b7280; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #065f46; color: #fff; padding: 8px 6px; text-align: left; font-size: 10px; }
        td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .currency { text-align: right; font-variant-numeric: tabular-nums; }
        tfoot td { font-weight: 700; background: #ecfdf5; border-top: 2px solid #065f46; }
        .footer { margin-top: 20px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ 'التقارير المالية' }}</h1>
    <p class="subtitle">
        {{ 'الفترة' }}:
        {{ $dateFrom ?? '–' }} → {{ $dateTo ?? '–' }}
        &nbsp;|&nbsp;
        {{ 'تاريخ الإنشاء' }}: {{ now()->format('Y-m-d H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ 'التاريخ' }}</th>
                <th>{{ 'الرحلات' }}</th>
                <th class="currency">{{ 'إجمالي الإيرادات' }}</th>
                <th class="currency">{{ 'العمولة' }}</th>
                <th class="currency">{{ 'أرباح السائقين' }}</th>
                <th class="currency">{{ 'خصومات الكوبونات' }}</th>
                <th class="currency">{{ 'رسوم الإلغاء' }}</th>
                <th class="currency">{{ 'رسوم الانتظار' }}</th>
                <th class="currency">{{ 'صافي الإيرادات' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->date }}</td>
                    <td>{{ number_format($row->total_trips) }}</td>
                    <td class="currency">{{ number_format((float) $row->total_revenue, 2) }}</td>
                    <td class="currency">{{ number_format((float) $row->commission, 2) }}</td>
                    <td class="currency">{{ number_format((float) $row->driver_earnings, 2) }}</td>
                    <td class="currency">{{ number_format((float) $row->coupon_discounts, 2) }}</td>
                    <td class="currency">{{ number_format((float) $row->cancellation_fees, 2) }}</td>
                    <td class="currency">{{ number_format((float) $row->waiting_fees, 2) }}</td>
                    <td class="currency">{{ number_format((float) $row->net_revenue, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:20px; color:#9ca3af;">
                        {{ 'لا توجد بيانات للفترة المحددة' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
        <tfoot>
            <tr>
                <td>{{ 'الإجمالي' }}</td>
                <td>{{ number_format($rows->sum('total_trips')) }}</td>
                <td class="currency">{{ number_format($rows->sum('total_revenue'), 2) }}</td>
                <td class="currency">{{ number_format($rows->sum('commission'), 2) }}</td>
                <td class="currency">{{ number_format($rows->sum('driver_earnings'), 2) }}</td>
                <td class="currency">{{ number_format($rows->sum('coupon_discounts'), 2) }}</td>
                <td class="currency">{{ number_format($rows->sum('cancellation_fees'), 2) }}</td>
                <td class="currency">{{ number_format($rows->sum('waiting_fees'), 2) }}</td>
                <td class="currency">{{ number_format($rows->sum('net_revenue'), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <p class="footer">{{ config('app.name') }} &mdash; {{ 'التقارير المالية' }}</p>
</body>
</html>
