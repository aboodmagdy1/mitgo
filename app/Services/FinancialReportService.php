<?php

namespace App\Services;

use App\Enums\TripStatus;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialReportService
{
    private const CACHE_TTL = 300;
    private const TAX_RATE = 0.15;

    public function __construct(private readonly GeneralSettings $settings) {}

    // -------------------------------------------------------------------------
    // Stat Cards
    // -------------------------------------------------------------------------

    /**
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array<string, mixed>
     */
    public function getStatCards(?array $dateRange): array
    {
        $cacheKey = 'financial_report:stats:' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, fn () => $this->computeStatCards($dateRange));
    }

    /**
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array<string, mixed>
     */
    private function computeStatCards(?array $dateRange): array
    {
        $completed = TripStatus::COMPLETED->value;

        Log::debug('[FinancialReport] computeStatCards', [
            'date_range' => $dateRange
                ? [$dateRange[0]->toDateTimeString(), $dateRange[1]->toDateTimeString()]
                : 'ALL',
            'completed_status_value' => $completed,
        ]);

        // Revenue from completed trips joined with trip_payments (all payment statuses).
        // Primary filter is trips.status = COMPLETED, matching the dashboard approach.
        $completedBase = DB::table('trips as t')
            ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
            ->where('t.status', $completed);

        if ($dateRange) {
            $completedBase->whereBetween('t.ended_at', $dateRange);
        }

        $completedRow = (clone $completedBase)->selectRaw(
            'COALESCE(SUM(tp.final_amount), 0)      AS total_revenue,
             COALESCE(SUM(tp.commission_amount), 0)  AS company_profit,
             COALESCE(SUM(tp.driver_earning), 0)     AS driver_earnings,
             COALESCE(AVG(tp.final_amount), 0)       AS avg_trip_fare,
             COALESCE(SUM(tp.coupon_discount), 0)    AS coupon_discounts,
             COUNT(*)                                AS completed_count'
        )->first();

        // Pending payments: trip payment created but not yet confirmed by driver/gateway.
        $pendingQuery = DB::table('trip_payments')->where('status', 0);
        if ($dateRange) {
            $pendingQuery->whereBetween('created_at', $dateRange);
        }
        $pendingRow = $pendingQuery->selectRaw(
            'COUNT(*) AS pending_count,
             COALESCE(SUM(final_amount), 0) AS pending_amount'
        )->first();

        // Refunded payments.
        $refundedQuery = DB::table('trip_payments')->where('status', 3);
        if ($dateRange) {
            $refundedQuery->whereBetween('created_at', $dateRange);
        }
        $refundedRow = $refundedQuery->selectRaw(
            'COALESCE(SUM(final_amount), 0) AS refunded_amount'
        )->first();

        // Cancellation & waiting fees from trips directly (no payment join needed).
        $tripsQuery = DB::table('trips')->whereIn('status', [
            TripStatus::CANCELLED_BY_DRIVER->value,
            TripStatus::CANCELLED_BY_RIDER->value,
            TripStatus::CANCELLED_BY_SYSTEM->value,
            TripStatus::COMPLETED->value,
        ]);
        if ($dateRange) {
            $tripsQuery->whereBetween('ended_at', $dateRange);
        }
        $tripsRow = $tripsQuery->selectRaw(
            'COALESCE(SUM(cancellation_fee), 0) AS cancellation_fees,
             COALESCE(SUM(waiting_fee), 0)       AS waiting_fees'
        )->first();

        // Wallet balances (all users — no date filter, this is a point-in-time balance).
        $walletBalance = DB::table('wallets')->sum('balance')/100;

        Log::debug('[FinancialReport] completedRow raw', (array) $completedRow);
        Log::debug('[FinancialReport] pendingRow raw',   (array) $pendingRow);
        Log::debug('[FinancialReport] refundedRow raw',  (array) $refundedRow);
        Log::debug('[FinancialReport] tripsRow raw',     (array) $tripsRow);
        Log::debug('[FinancialReport] walletBalance raw', ['wallet_balance' => $walletBalance]);

        // Sanity-check: total completed trips (no payment join) to compare
        $completedTripCount = DB::table('trips')->where('status', $completed)->count();
        $completedTripFare  = DB::table('trips')->where('status', $completed)->sum('actual_fare');
        Log::debug('[FinancialReport] trips table sanity-check', [
            'completed_trips_count'     => $completedTripCount,
            'sum_actual_fare_from_trips' => $completedTripFare,
            'trip_payments_joined_count' => $completedRow->completed_count ?? 0,
        ]);

        $totalRevenue = (float) ($completedRow->total_revenue ?? 0);

        $result = [
            'total_revenue'     => $totalRevenue,
            'company_profit'    => (float) ($completedRow->company_profit ?? 0),
            'driver_earnings'   => (float) ($completedRow->driver_earnings ?? 0),
            'avg_trip_fare'     => (float) ($completedRow->avg_trip_fare ?? 0),
            'total_taxes'       => $totalRevenue * self::TAX_RATE,
            'coupon_discounts'  => (float) ($completedRow->coupon_discounts ?? 0),
            'cancellation_fees' => (float) ($tripsRow->cancellation_fees ?? 0),
            'waiting_fees'      => (float) ($tripsRow->waiting_fees ?? 0),
            'pending_count'     => (int)   ($pendingRow->pending_count ?? 0),
            'pending_amount'    => (float) ($pendingRow->pending_amount ?? 0),
            'refunded_amount'   => (float) ($refundedRow->refunded_amount ?? 0),
            'wallet_balance'    => (float) $walletBalance,
        ];

        Log::info('[FinancialReport] computeStatCards FINAL result', $result);

        return $result;
    }

    // -------------------------------------------------------------------------
    // Charts
    // -------------------------------------------------------------------------

    /**
     * Revenue grouped by day over the date range.
     *
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array{labels:array<string>, data:array<float>}
     */
    public function getRevenueOverTime(?array $dateRange): array
    {
        $cacheKey = 'financial_report:revenue_over_time:' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, function () use ($dateRange) {
            $query = DB::table('trips as t')
                ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
                ->where('t.status', TripStatus::COMPLETED->value)
                ->selectRaw("DATE(t.ended_at) AS day, COALESCE(SUM(tp.final_amount), 0) AS revenue")
                ->groupBy('day')
                ->orderBy('day');

            if ($dateRange) {
                $query->whereBetween('t.ended_at', $dateRange);
            }

            $rows = $query->get();

            Log::debug('[FinancialReport] getRevenueOverTime', [
                'row_count' => $rows->count(),
                'rows'      => $rows->toArray(),
            ]);

            return [
                'labels' => $rows->pluck('day')->map(fn ($d) => Carbon::parse($d)->format('M d'))->toArray(),
                'data'   => $rows->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->toArray(),
            ];
        });
    }

    /**
     * Payment method distribution (count + amount).
     *
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array{labels:array<string>, counts:array<int>, amounts:array<float>}
     */
    public function getPaymentMethodsDistribution(?array $dateRange): array
    {
        $cacheKey = 'financial_report:payment_methods:' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, function () use ($dateRange) {
            $query = DB::table('trips as t')
                ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
                ->join('payment_methods as pm', 'tp.payment_method_id', '=', 'pm.id')
                ->where('t.status', TripStatus::COMPLETED->value)
                ->selectRaw("pm.name, COUNT(*) AS cnt, COALESCE(SUM(tp.final_amount), 0) AS total")
                ->groupBy('pm.id', 'pm.name')
                ->orderByDesc('total');

            if ($dateRange) {
                $query->whereBetween('t.ended_at', $dateRange);
            }

            $rows = $query->get();

            $labels = $rows->map(function ($row) {
                $name = $row->name;
                if (is_string($name)) {
                    $decoded = json_decode($name, true);
                    if (is_array($decoded)) {
                        return $decoded[app()->getLocale()] ?? $decoded['en'] ?? $name;
                    }
                }
                return $name;
            })->toArray();

            Log::debug('[FinancialReport] getPaymentMethodsDistribution', [
                'row_count' => $rows->count(),
                'rows'      => $rows->toArray(),
            ]);

            return [
                'labels'  => $labels,
                'counts'  => $rows->pluck('cnt')->map(fn ($v) => (int) $v)->toArray(),
                'amounts' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->toArray(),
            ];
        });
    }

    /**
     * Monthly commission vs driver earnings comparison.
     *
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array{labels:array<string>, commission:array<float>, driver_earnings:array<float>}
     */
    public function getCommissionVsDriverEarnings(?array $dateRange): array
    {
        $cacheKey = 'financial_report:commission_vs_driver:' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, function () use ($dateRange) {
            $query = DB::table('trips as t')
                ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
                ->where('t.status', TripStatus::COMPLETED->value)
                ->selectRaw(
                    "DATE_FORMAT(t.ended_at, '%Y-%m') AS month,
                     COALESCE(SUM(tp.commission_amount), 0) AS commission,
                     COALESCE(SUM(tp.driver_earning), 0)    AS driver_earnings"
                )
                ->groupBy('month')
                ->orderBy('month');

            if ($dateRange) {
                $query->whereBetween('t.ended_at', $dateRange);
            }

            $rows = $query->get();

            Log::debug('[FinancialReport] getCommissionVsDriverEarnings', [
                'row_count' => $rows->count(),
                'rows'      => $rows->toArray(),
            ]);

            return [
                'labels'          => $rows->pluck('month')->map(fn ($m) => Carbon::parse($m . '-01')->format('M Y'))->toArray(),
                'commission'      => $rows->pluck('commission')->map(fn ($v) => round((float) $v, 2))->toArray(),
                'driver_earnings' => $rows->pluck('driver_earnings')->map(fn ($v) => round((float) $v, 2))->toArray(),
            ];
        });
    }

    /**
     * Top N earning drivers.
     *
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array{labels:array<string>, data:array<float>}
     */
    public function getTopEarningDrivers(?array $dateRange, int $limit = 10): array
    {
        $cacheKey = 'financial_report:top_drivers:' . $limit . ':' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, function () use ($dateRange, $limit) {
            $query = DB::table('trips as t')
                ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
                ->join('drivers as d', 't.driver_id', '=', 'd.id')
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->where('t.status', TripStatus::COMPLETED->value)
                ->selectRaw("u.name, COALESCE(SUM(tp.driver_earning), 0) AS total_earning")
                ->groupBy('d.id', 'u.name')
                ->orderByDesc('total_earning')
                ->limit($limit);

            if ($dateRange) {
                $query->whereBetween('t.ended_at', $dateRange);
            }

            $rows = $query->get();

            Log::debug('[FinancialReport] getTopEarningDrivers', [
                'row_count' => $rows->count(),
                'rows'      => $rows->toArray(),
            ]);

            return [
                'labels' => $rows->pluck('name')->toArray(),
                'data'   => $rows->pluck('total_earning')->map(fn ($v) => round((float) $v, 2))->toArray(),
            ];
        });
    }

    /**
     * Coupon usage count and total discount given.
     *
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array{labels:array<string>, counts:array<int>, discounts:array<float>}
     */
    public function getCouponImpact(?array $dateRange): array
    {
        $cacheKey = 'financial_report:coupon_impact:' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, function () use ($dateRange) {
            $query = DB::table('trips as t')
                ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
                ->join('coupons as c', 'tp.coupon_id', '=', 'c.id')
                ->where('t.status', TripStatus::COMPLETED->value)
                ->whereNotNull('tp.coupon_id')
                ->selectRaw("c.code, COUNT(*) AS cnt, COALESCE(SUM(tp.coupon_discount), 0) AS total_discount")
                ->groupBy('c.id', 'c.code')
                ->orderByDesc('total_discount')
                ->limit(15);

            if ($dateRange) {
                $query->whereBetween('t.ended_at', $dateRange);
            }

            $rows = $query->get();

            Log::debug('[FinancialReport] getCouponImpact', [
                'row_count' => $rows->count(),
                'rows'      => $rows->toArray(),
            ]);

            return [
                'labels'    => $rows->pluck('code')->toArray(),
                'counts'    => $rows->pluck('cnt')->map(fn ($v) => (int) $v)->toArray(),
                'discounts' => $rows->pluck('total_discount')->map(fn ($v) => round((float) $v, 2))->toArray(),
            ];
        });
    }

    /**
     * Trip count vs revenue by day.
     *
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array{labels:array<string>, trips:array<int>, revenue:array<float>}
     */
    public function getTripsVsRevenue(?array $dateRange): array
    {
        $cacheKey = 'financial_report:trips_vs_revenue:' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, function () use ($dateRange) {
            $query = DB::table('trips as t')
                ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
                ->where('t.status', TripStatus::COMPLETED->value)
                ->selectRaw(
                    "DATE(t.ended_at) AS day,
                     COUNT(*) AS trip_count,
                     COALESCE(SUM(tp.final_amount), 0) AS revenue"
                )
                ->groupBy('day')
                ->orderBy('day');

            if ($dateRange) {
                $query->whereBetween('t.ended_at', $dateRange);
            }

            $rows = $query->get();

            Log::debug('[FinancialReport] getTripsVsRevenue', [
                'row_count' => $rows->count(),
                'rows'      => $rows->toArray(),
            ]);

            return [
                'labels'  => $rows->pluck('day')->map(fn ($d) => Carbon::parse($d)->format('M d'))->toArray(),
                'trips'   => $rows->pluck('trip_count')->map(fn ($v) => (int) $v)->toArray(),
                'revenue' => $rows->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->toArray(),
            ];
        });
    }

    /**
     * Revenue grouped by zone.
     *
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array{labels:array<string>, data:array<float>}
     */
    public function getRevenueByZone(?array $dateRange): array
    {
        $cacheKey = 'financial_report:revenue_by_zone:' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, function () use ($dateRange) {
            $locale = app()->getLocale();

            $query = DB::table('trips as t')
                ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
                ->join('zones as z', 't.zone_id', '=', 'z.id')
                ->where('t.status', TripStatus::COMPLETED->value)
                ->selectRaw("z.name, COALESCE(SUM(tp.final_amount), 0) AS revenue")
                ->groupBy('z.id', 'z.name')
                ->orderByDesc('revenue');

            if ($dateRange) {
                $query->whereBetween('t.ended_at', $dateRange);
            }

            $rows = $query->get();

            $labels = $rows->map(function ($row) use ($locale) {
                $name = $row->name;
                if (is_string($name)) {
                    $decoded = json_decode($name, true);
                    if (is_array($decoded)) {
                        return $decoded[$locale] ?? $decoded['en'] ?? $name;
                    }
                }
                return $name;
            })->toArray();

            Log::debug('[FinancialReport] getRevenueByZone', [
                'row_count' => $rows->count(),
                'rows'      => $rows->toArray(),
            ]);

            return [
                'labels' => $labels,
                'data'   => $rows->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->toArray(),
            ];
        });
    }

    /**
     * Revenue grouped by hour of day (0–23).
     *
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return array{labels:array<string>, data:array<float>}
     */
    public function getRevenueByHour(?array $dateRange): array
    {
        $cacheKey = 'financial_report:revenue_by_hour:' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, function () use ($dateRange) {
            $query = DB::table('trips as t')
                ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
                ->where('t.status', TripStatus::COMPLETED->value)
                ->selectRaw(
                    "HOUR(t.ended_at) AS hour,
                     COALESCE(SUM(tp.final_amount), 0) AS revenue,
                     COUNT(*) AS cnt"
                )
                ->groupBy('hour')
                ->orderBy('hour');

            if ($dateRange) {
                $query->whereBetween('t.ended_at', $dateRange);
            }

            $rows   = $query->get()->keyBy('hour');
            $labels = [];
            $data   = [];

            for ($h = 0; $h < 24; $h++) {
                $labels[] = sprintf('%02d:00', $h);
                $data[]   = round((float) ($rows->get($h)?->revenue ?? 0), 2);
            }

            Log::debug('[FinancialReport] getRevenueByHour', [
                'non_zero_hours' => $rows->count(),
                'raw_rows'       => $rows->toArray(),
                'data_totals'    => array_sum($data),
            ]);

            return ['labels' => $labels, 'data' => $data];
        });
    }

    // -------------------------------------------------------------------------
    // Daily Summary Table
    // -------------------------------------------------------------------------

    /**
     * Daily financial summary rows.
     *
     * @param  array{Carbon,Carbon}|null  $dateRange
     * @return Collection<int, object>
     */
    public function getDailySummary(?array $dateRange): Collection
    {
        $cacheKey = 'financial_report:daily_summary:' . $this->cacheKeySuffix($dateRange);

        return $this->remember($cacheKey, function () use ($dateRange) {
            $query = DB::table('trips as t')
                ->join('trip_payments as tp', 'tp.trip_id', '=', 't.id')
                ->where('t.status', TripStatus::COMPLETED->value)
                ->selectRaw(
                    "DATE(t.ended_at)                      AS date,
                     COUNT(*)                              AS total_trips,
                     COALESCE(SUM(tp.final_amount), 0)    AS total_revenue,
                     COALESCE(SUM(tp.commission_amount),0) AS commission,
                     COALESCE(SUM(tp.driver_earning), 0)  AS driver_earnings,
                     COALESCE(SUM(tp.coupon_discount), 0) AS coupon_discounts,
                     COALESCE(SUM(t.cancellation_fee), 0) AS cancellation_fees,
                     COALESCE(SUM(t.waiting_fee), 0)      AS waiting_fees,
                     COALESCE(SUM(tp.final_amount), 0) - COALESCE(SUM(tp.commission_amount), 0) AS net_revenue"
                )
                ->groupByRaw('DATE(t.ended_at)')
                ->orderByRaw('DATE(t.ended_at) DESC');

            if ($dateRange) {
                $query->whereBetween('t.ended_at', $dateRange);
            }

            $result = $query->get();

            Log::debug('[FinancialReport] getDailySummary', [
                'row_count'    => $result->count(),
                'rows'         => $result->toArray(),
            ]);

            return $result;
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param  array{Carbon,Carbon}|null  $dateRange */
    private function cacheKeySuffix(?array $dateRange): string
    {
        if (! $dateRange) {
            return 'all';
        }

        return md5($dateRange[0]->toDateString() . '|' . $dateRange[1]->toDateString());
    }

    /**
     * Try Redis first; fall back to default cache driver.
     *
     * @template T
     * @param  \Closure(): T  $callback
     * @return T
     */
    private function remember(string $key, \Closure $callback): mixed
    {
        try {
            return Cache::store('redis')->remember($key, self::CACHE_TTL, $callback);
        } catch (\Throwable) {
            return Cache::remember($key, self::CACHE_TTL, $callback);
        }
    }

    /** Forget all financial report cache keys matching a date range suffix. */
    public function forgetCache(?array $dateRange): void
    {
        $suffix = $this->cacheKeySuffix($dateRange);

        $keys = [
            "financial_report:stats:{$suffix}",
            "financial_report:revenue_over_time:{$suffix}",
            "financial_report:payment_methods:{$suffix}",
            "financial_report:commission_vs_driver:{$suffix}",
            "financial_report:top_drivers:10:{$suffix}",
            "financial_report:coupon_impact:{$suffix}",
            "financial_report:trips_vs_revenue:{$suffix}",
            "financial_report:revenue_by_zone:{$suffix}",
            "financial_report:revenue_by_hour:{$suffix}",
            "financial_report:daily_summary:{$suffix}",
        ];

        foreach ($keys as $key) {
            try {
                Cache::store('redis')->forget($key);
            } catch (\Throwable) {
            }
            Cache::forget($key);
        }
    }
}
