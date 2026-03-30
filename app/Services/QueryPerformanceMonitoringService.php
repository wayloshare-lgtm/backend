<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Query Performance Monitoring Service
 * 
 * Monitors database query execution times, logs slow queries,
 * tracks query statistics, and provides performance metrics.
 */
class QueryPerformanceMonitoringService
{
    /**
     * Threshold for slow queries in milliseconds
     */
    private const SLOW_QUERY_THRESHOLD_MS = 50;

    /**
     * Cache key prefix for query statistics
     */
    private const STATS_CACHE_PREFIX = 'query_stats:';

    /**
     * Cache duration for statistics in minutes
     */
    private const STATS_CACHE_DURATION = 60;

    /**
     * Initialize query performance monitoring
     * Registers listeners for database queries
     *
     * @return void
     */
    public static function initialize(): void
    {
        DB::listen(function ($query) {
            $service = new self();
            $service->handleQuery($query);
        });
    }

    /**
     * Handle a database query and log if it exceeds threshold
     *
     * @param object $query Query object with sql, bindings, and time properties
     * @return void
     */
    public function handleQuery(object $query): void
    {
        $executionTime = $query->time;

        // Track query statistics
        $this->trackQueryStatistics($query->sql, $executionTime);

        // Log slow queries
        if ($executionTime > self::SLOW_QUERY_THRESHOLD_MS) {
            $this->logSlowQuery($query);
        }
    }

    /**
     * Track query statistics (count, average time, max time)
     *
     * @param string $sql The SQL query
     * @param float $executionTime Execution time in milliseconds
     * @return void
     */
    private function trackQueryStatistics(string $sql, float $executionTime): void
    {
        $queryHash = $this->hashQuery($sql);
        $cacheKey = self::STATS_CACHE_PREFIX . $queryHash;

        // Get existing stats
        $stats = Cache::get($cacheKey, [
            'query' => $sql,
            'count' => 0,
            'total_time' => 0,
            'avg_time' => 0,
            'max_time' => 0,
            'min_time' => PHP_FLOAT_MAX,
        ]);

        // Update statistics
        $stats['count']++;
        $stats['total_time'] += $executionTime;
        $stats['avg_time'] = $stats['total_time'] / $stats['count'];
        $stats['max_time'] = max($stats['max_time'], $executionTime);
        $stats['min_time'] = min($stats['min_time'], $executionTime);
        $stats['last_executed_at'] = now()->toIso8601String();

        // Store updated stats
        Cache::put($cacheKey, $stats, now()->addMinutes(self::STATS_CACHE_DURATION));

        // Track the key for retrieval
        $keys = Cache::get('query_stats_keys', []);
        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::put('query_stats_keys', $keys, now()->addMinutes(self::STATS_CACHE_DURATION));
        }
    }

    /**
     * Log a slow query
     *
     * @param object $query Query object
     * @return void
     */
    private function logSlowQuery(object $query): void
    {
        $context = [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'execution_time_ms' => round($query->time, 2),
            'threshold_ms' => self::SLOW_QUERY_THRESHOLD_MS,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('queries')->warning('Slow Query Detected', $context);
    }

    /**
     * Get query statistics for a specific query
     *
     * @param string $sql The SQL query
     * @return array|null Query statistics or null if not found
     */
    public function getQueryStats(string $sql): ?array
    {
        $queryHash = $this->hashQuery($sql);
        $cacheKey = self::STATS_CACHE_PREFIX . $queryHash;

        return Cache::get($cacheKey);
    }

    /**
     * Get all query statistics
     *
     * @return array Array of all query statistics
     */
    public function getAllQueryStats(): array
    {
        $stats = [];
        
        try {
            // Try to get keys from Redis if available
            $store = Cache::getStore();
            if (method_exists($store, 'connection')) {
                $keys = $store->connection()->keys(self::STATS_CACHE_PREFIX . '*');
            } else {
                // Fallback for array/file cache - use a separate tracking mechanism
                $keys = Cache::get('query_stats_keys', []);
            }

            foreach ($keys as $key) {
                $data = Cache::get($key);
                if ($data) {
                    $stats[] = $data;
                }
            }
        } catch (\Exception $e) {
            // If cache retrieval fails, return empty array
            return [];
        }

        // Sort by average execution time (descending)
        usort($stats, function ($a, $b) {
            return $b['avg_time'] <=> $a['avg_time'];
        });

        return $stats;
    }

    /**
     * Get slow queries (queries exceeding threshold)
     *
     * @return array Array of slow query statistics
     */
    public function getSlowQueries(): array
    {
        $allStats = $this->getAllQueryStats();
        
        return array_filter($allStats, function ($stat) {
            return $stat['avg_time'] > self::SLOW_QUERY_THRESHOLD_MS;
        });
    }

    /**
     * Get top N slowest queries
     *
     * @param int $limit Number of queries to return
     * @return array Array of slowest queries
     */
    public function getTopSlowestQueries(int $limit = 10): array
    {
        $allStats = $this->getAllQueryStats();
        return array_slice($allStats, 0, $limit);
    }

    /**
     * Get query statistics summary
     *
     * @return array Summary statistics
     */
    public function getStatsSummary(): array
    {
        $allStats = $this->getAllQueryStats();

        if (empty($allStats)) {
            return [
                'total_queries' => 0,
                'total_execution_time_ms' => 0,
                'average_execution_time_ms' => 0,
                'max_execution_time_ms' => 0,
                'slow_queries_count' => 0,
            ];
        }

        $totalQueries = array_sum(array_column($allStats, 'count'));
        $totalTime = array_sum(array_column($allStats, 'total_time'));
        $maxTime = max(array_column($allStats, 'max_time'));
        $slowQueriesCount = count($this->getSlowQueries());

        return [
            'total_queries' => $totalQueries,
            'total_execution_time_ms' => round($totalTime, 2),
            'average_execution_time_ms' => round($totalTime / $totalQueries, 2),
            'max_execution_time_ms' => round($maxTime, 2),
            'slow_queries_count' => $slowQueriesCount,
            'unique_queries' => count($allStats),
        ];
    }

    /**
     * Clear all query statistics
     *
     * @return void
     */
    public function clearStats(): void
    {
        $keys = Cache::get('query_stats_keys', []);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget('query_stats_keys');

        Log::channel('queries')->info('Query statistics cleared');
    }

    /**
     * Generate a hash for a query to use as cache key
     *
     * @param string $sql The SQL query
     * @return string Hash of the query
     */
    private function hashQuery(string $sql): string
    {
        // Normalize query by removing extra whitespace
        $normalized = preg_replace('/\s+/', ' ', trim($sql));
        return md5($normalized);
    }

    /**
     * Check if query performance is degrading
     *
     * @param float $degradationThreshold Percentage threshold (e.g., 20 for 20%)
     * @return array Array of queries with performance degradation
     */
    public function checkPerformanceDegradation(float $degradationThreshold = 20): array
    {
        $degradedQueries = [];
        $allStats = $this->getAllQueryStats();

        foreach ($allStats as $stat) {
            // If max time is significantly higher than average, flag it
            $degradation = (($stat['max_time'] - $stat['avg_time']) / $stat['avg_time']) * 100;
            
            if ($degradation > $degradationThreshold) {
                $degradedQueries[] = [
                    'query' => $stat['query'],
                    'average_time_ms' => round($stat['avg_time'], 2),
                    'max_time_ms' => round($stat['max_time'], 2),
                    'degradation_percent' => round($degradation, 2),
                    'execution_count' => $stat['count'],
                ];
            }
        }

        return $degradedQueries;
    }

    /**
     * Get the slow query threshold
     *
     * @return int Threshold in milliseconds
     */
    public static function getSlowQueryThreshold(): int
    {
        return self::SLOW_QUERY_THRESHOLD_MS;
    }
}
