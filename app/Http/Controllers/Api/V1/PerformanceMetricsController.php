<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\QueryPerformanceMonitoringService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

/**
 * Performance Metrics Controller
 * 
 * Provides endpoints to view query performance metrics and statistics.
 * Requires admin authentication.
 */
class PerformanceMetricsController extends Controller
{
    private QueryPerformanceMonitoringService $monitoringService;

    public function __construct()
    {
        $this->monitoringService = new QueryPerformanceMonitoringService();
        // Require authentication for all endpoints
        $this->middleware('auth:sanctum');
    }

    /**
     * Get query performance summary
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $summary = $this->monitoringService->getStatsSummary();

        return response()->json([
            'success' => true,
            'data' => $summary,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get all query statistics
     *
     * @return JsonResponse
     */
    public function allQueries(): JsonResponse
    {
        $stats = $this->monitoringService->getAllQueryStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
            'count' => count($stats),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get slow queries
     *
     * @return JsonResponse
     */
    public function slowQueries(): JsonResponse
    {
        $slowQueries = $this->monitoringService->getSlowQueries();

        return response()->json([
            'success' => true,
            'data' => $slowQueries,
            'count' => count($slowQueries),
            'threshold_ms' => QueryPerformanceMonitoringService::getSlowQueryThreshold(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get top N slowest queries
     *
     * @return JsonResponse
     */
    public function topSlowest(): JsonResponse
    {
        $limit = request()->query('limit', 10);
        $topQueries = $this->monitoringService->getTopSlowestQueries((int) $limit);

        return response()->json([
            'success' => true,
            'data' => $topQueries,
            'count' => count($topQueries),
            'limit' => $limit,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check for performance degradation
     *
     * @return JsonResponse
     */
    public function degradation(): JsonResponse
    {
        $threshold = request()->query('threshold', 20);
        $degradedQueries = $this->monitoringService->checkPerformanceDegradation((float) $threshold);

        return response()->json([
            'success' => true,
            'data' => $degradedQueries,
            'count' => count($degradedQueries),
            'degradation_threshold_percent' => $threshold,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Clear query statistics
     *
     * @return JsonResponse
     */
    public function clearStats(): JsonResponse
    {
        $this->monitoringService->clearStats();

        return response()->json([
            'success' => true,
            'message' => 'Query statistics cleared successfully',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
