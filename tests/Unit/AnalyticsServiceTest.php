<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService();
    }

    public function test_overview_returns_expected_keys()
    {
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now();

        $result = $this->service->getOverviewStats($from, $to);

        $this->assertArrayHasKey('total_employees', $result);
        $this->assertArrayHasKey('total_checkins', $result);
        $this->assertArrayHasKey('avg_late_minutes', $result);
        $this->assertArrayHasKey('total_leaves', $result);
    }

    public function test_daily_trends_returns_array()
    {
        $from = Carbon::now()->subDays(7);
        $to = Carbon::now();

        $result = $this->service->getDailyTrends($from, $to);
        $this->assertIsArray($result);
    }

    public function test_heatmap_returns_array()
    {
        $from = Carbon::now()->subDays(30);
        $to = Carbon::now();

        $result = $this->service->getHeatmapData($from, $to);
        $this->assertIsArray($result);
    }

    public function test_branch_stats_with_no_data()
    {
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now();

        $result = $this->service->getBranchStats($from, $to);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_financial_report_returns_expected_keys()
    {
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now();

        $result = $this->service->getFinancialReport($from, $to);

        $this->assertArrayHasKey('overtime', $result);
        $this->assertArrayHasKey('deductions', $result);
        $this->assertArrayHasKey('total_overtime_cost', $result);
        $this->assertArrayHasKey('total_deductions', $result);
    }
}
