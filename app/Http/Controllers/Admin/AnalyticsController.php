<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * لوحة التحليلات التفاعلية
     */
    public function index(Request $request)
    {
        $from = $request->input('from', today()->subDays(30)->toDateString());
        $to = $request->input('to', today()->toDateString());
        $branchId = $request->input('branch_id');

        $branches = Branch::active()->get();
        $overview = AnalyticsService::getOverviewStats($from, $to);
        $trends = AnalyticsService::getDailyTrends($from, $to, $branchId);
        $heatmap = AnalyticsService::getHeatmapData($from, $to, $branchId);
        $branchStats = AnalyticsService::getBranchStats($from, $to);
        $topLate = AnalyticsService::getTopLateEmployees($from, $to);
        $financial = AnalyticsService::getFinancialReport($from, $to);

        return view('admin.analytics', compact(
            'branches', 'overview', 'trends', 'heatmap',
            'branchStats', 'topLate', 'financial', 'from', 'to', 'branchId'
        ));
    }

    /**
     * بيانات التحليلات بصيغة JSON (لتحديث AJAX)
     */
    public function data(Request $request)
    {
        $from = $request->input('from', today()->subDays(30)->toDateString());
        $to = $request->input('to', today()->toDateString());
        $branchId = $request->input('branch_id');

        return response()->json([
            'overview'     => AnalyticsService::getOverviewStats($from, $to),
            'trends'       => AnalyticsService::getDailyTrends($from, $to, $branchId),
            'heatmap'      => AnalyticsService::getHeatmapData($from, $to, $branchId),
            'branch_stats' => AnalyticsService::getBranchStats($from, $to),
            'top_late'     => AnalyticsService::getTopLateEmployees($from, $to),
            'financial'    => AnalyticsService::getFinancialReport($from, $to),
        ]);
    }
}
