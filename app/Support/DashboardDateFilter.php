<?php

namespace App\Support;

use Carbon\Carbon;

class DashboardDateFilter
{
    public const SESSION_KEY = 'dashboard_date_filter';

    public const PRESET_NONE = 'none';
    public const PRESET_LAST_WEEK = 'last_week';
    public const PRESET_LAST_MONTH = 'last_month';
    public const PRESET_LAST_3_MONTHS = 'last_3_months';
    public const PRESET_LAST_YEAR = 'last_year';
    public const PRESET_CUSTOM = 'custom';

    /**
     * @return array{Carbon, Carbon}|null Returns [from, to] or null when no filter applied
     */
    public static function getDateRange(): ?array
    {
        $filter = session(self::SESSION_KEY, self::defaultFilter());

        if ($filter['preset'] === self::PRESET_NONE) {
            return null;
        }

        if ($filter['preset'] === self::PRESET_CUSTOM) {
            $from = $filter['date_from'] ?? '';
            $to = $filter['date_to'] ?? '';
            if (empty($from) || empty($to)) {
                return null;
            }
            // Parse in app timezone for consistent date boundaries
            $fromDate = Carbon::parse($from, config('app.timezone'))->startOfDay();
            $toDate = Carbon::parse($to, config('app.timezone'))->endOfDay();
            if ($fromDate->gt($toDate)) {
                [$from, $to] = [$to, $from];
                $fromDate = Carbon::parse($from, config('app.timezone'))->startOfDay();
                $toDate = Carbon::parse($to, config('app.timezone'))->endOfDay();
            }
            return [$fromDate, $toDate];
        }

        return self::getPresetRange($filter['preset']);
    }

    public static function getPresetRange(string $preset): array
    {
        $now = Carbon::now();

        return match ($preset) {
            self::PRESET_LAST_WEEK => [
                $now->copy()->subWeek()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            self::PRESET_LAST_MONTH => [
                $now->copy()->subMonth()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            self::PRESET_LAST_3_MONTHS => [
                $now->copy()->subMonths(3)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            self::PRESET_LAST_YEAR => [
                $now->copy()->subYear()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            default => [
                $now->copy()->subMonth()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
        };
    }

    public static function defaultFilter(): array
    {
        return [
            'preset' => self::PRESET_NONE,
            'date_from' => Carbon::now()->subMonth()->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d'),
        ];
    }

    public static function apply(array $filter): void
    {
        session([self::SESSION_KEY => array_merge(self::defaultFilter(), $filter)]);
    }

    public static function hasActiveFilter(): bool
    {
        $filter = session(self::SESSION_KEY, self::defaultFilter());

        return $filter['preset'] !== self::PRESET_NONE;
    }

    public static function getCurrentFilter(): array
    {
        return session(self::SESSION_KEY, self::defaultFilter());
    }

    /**
     * Build a cache key suffix for the current filter state.
     * Used for Redis cache keys to ensure different filters get different cached results.
     */
    public static function getCacheKeySuffix(): string
    {
        $filter = self::getCurrentFilter();

        return md5(json_encode([
            $filter['preset'] ?? 'none',
            $filter['date_from'] ?? '',
            $filter['date_to'] ?? '',
        ]));
    }
}
