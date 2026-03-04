<?php

namespace Tests\Unit\Support;

use App\Support\DashboardDateFilter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDateFilterTest extends TestCase
{
    protected function tearDown(): void
    {
        session()->forget(DashboardDateFilter::SESSION_KEY);
        parent::tearDown();
    }

    public function test_custom_filter_returns_correct_date_range(): void
    {
        DashboardDateFilter::apply([
            'preset' => DashboardDateFilter::PRESET_CUSTOM,
            'date_from' => '2025-01-15',
            'date_to' => '2025-01-20',
        ]);

        $range = DashboardDateFilter::getDateRange();

        $this->assertNotNull($range);
        $this->assertCount(2, $range);
        $this->assertEquals('2025-01-15 00:00:00', $range[0]->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-20 23:59:59', $range[1]->format('Y-m-d H:i:s'));
    }

    public function test_custom_filter_swaps_dates_when_from_after_to(): void
    {
        DashboardDateFilter::apply([
            'preset' => DashboardDateFilter::PRESET_CUSTOM,
            'date_from' => '2025-01-20',
            'date_to' => '2025-01-15',
        ]);

        $range = DashboardDateFilter::getDateRange();

        $this->assertNotNull($range);
        $this->assertEquals('2025-01-15 00:00:00', $range[0]->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-20 23:59:59', $range[1]->format('Y-m-d H:i:s'));
    }

    public function test_custom_filter_returns_null_when_dates_empty(): void
    {
        DashboardDateFilter::apply([
            'preset' => DashboardDateFilter::PRESET_CUSTOM,
            'date_from' => '',
            'date_to' => '',
        ]);

        $range = DashboardDateFilter::getDateRange();

        $this->assertNull($range);
    }

    public function test_preset_none_returns_null(): void
    {
        DashboardDateFilter::apply(['preset' => DashboardDateFilter::PRESET_NONE]);

        $this->assertNull(DashboardDateFilter::getDateRange());
    }

    public function test_last_week_preset_returns_correct_range(): void
    {
        Carbon::setTestNow('2025-03-04 12:00:00');

        DashboardDateFilter::apply(['preset' => DashboardDateFilter::PRESET_LAST_WEEK]);

        $range = DashboardDateFilter::getDateRange();

        $this->assertNotNull($range);
        $this->assertEquals('2025-02-25 00:00:00', $range[0]->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-03-04 23:59:59', $range[1]->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }
}
