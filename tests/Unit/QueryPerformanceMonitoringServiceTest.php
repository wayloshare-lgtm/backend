<?php

namespace Tests\Unit;

use App\Services\QueryPerformanceMonitoringService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class QueryPerformanceMonitoringServiceTest extends TestCase
{
    private QueryPerformanceMonitoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QueryPerformanceMonitoringService();
        Cache::flush();
    }

    /**
     * Test that slow query threshold is correct
     */
    public function test_slow_query_threshold_is_50ms(): void
    {
        $threshold = QueryPerformanceMonitoringService::getSlowQueryThreshold();
        $this->assertEquals(50, $threshold);
    }

    /**
     * Test that query statistics are tracked
     */
    public function test_query_statistics_are_tracked(): void
    {
        $query = (object) [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [1],
            'time' => 25.5,
        ];

        $this->service->handleQuery($query);

        $stats = $this->service->getAllQueryStats();
        $this->assertNotEmpty($stats);
        $this->assertEquals(1, $stats[0]['count']);
        $this->assertEquals(25.5, $stats[0]['avg_time']);
    }

    /**
     * Test that multiple queries are aggregated
     */
    public function test_multiple_queries_are_aggregated(): void
    {
        $query = (object) [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [1],
            'time' => 20,
        ];

        $this->service->handleQuery($query);
        $this->service->handleQuery($query);
        $this->service->handleQuery($query);

        $stats = $this->service->getAllQueryStats();
        $this->assertEquals(1, count($stats));
        $this->assertEquals(3, $stats[0]['count']);
        $this->assertEquals(20, $stats[0]['avg_time']);
    }

    /**
     * Test that max and min times are tracked
     */
    public function test_max_and_min_times_are_tracked(): void
    {
        $queries = [
            (object) ['sql' => 'SELECT * FROM users', 'bindings' => [], 'time' => 10],
            (object) ['sql' => 'SELECT * FROM users', 'bindings' => [], 'time' => 30],
            (object) ['sql' => 'SELECT * FROM users', 'bindings' => [], 'time' => 20],
        ];

        foreach ($queries as $query) {
            $this->service->handleQuery($query);
        }

        $stats = $this->service->getAllQueryStats();
        $this->assertEquals(30, $stats[0]['max_time']);
        $this->assertEquals(10, $stats[0]['min_time']);
        $this->assertEquals(20, $stats[0]['avg_time']);
    }

    /**
     * Test that slow queries are logged
     */
    public function test_slow_queries_are_logged(): void
    {
        Log::shouldReceive('channel')
            ->with('queries')
            ->andReturnSelf()
            ->shouldReceive('warning')
            ->once();

        $query = (object) [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [1],
            'time' => 75, // Exceeds 50ms threshold
        ];

        $this->service->handleQuery($query);
    }

    /**
     * Test that fast queries are not logged
     */
    public function test_fast_queries_are_not_logged(): void
    {
        Log::shouldReceive('channel')
            ->with('queries')
            ->andReturnSelf()
            ->shouldReceive('warning')
            ->never();

        $query = (object) [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [1],
            'time' => 25, // Below 50ms threshold
        ];

        $this->service->handleQuery($query);
    }

    /**
     * Test that slow queries can be retrieved
     */
    public function test_slow_queries_can_be_retrieved(): void
    {
        $queries = [
            (object) ['sql' => 'SELECT * FROM users', 'bindings' => [], 'time' => 30],
            (object) ['sql' => 'SELECT * FROM posts', 'bindings' => [], 'time' => 75],
            (object) ['sql' => 'SELECT * FROM comments', 'bindings' => [], 'time' => 100],
        ];

        foreach ($queries as $query) {
            $this->service->handleQuery($query);
        }

        $slowQueries = $this->service->getSlowQueries();
        $this->assertEquals(2, count($slowQueries));
    }

    /**
     * Test that top slowest queries are returned in order
     */
    public function test_top_slowest_queries_are_ordered(): void
    {
        $queries = [
            (object) ['sql' => 'SELECT * FROM users', 'bindings' => [], 'time' => 30],
            (object) ['sql' => 'SELECT * FROM posts', 'bindings' => [], 'time' => 75],
            (object) ['sql' => 'SELECT * FROM comments', 'bindings' => [], 'time' => 100],
        ];

        foreach ($queries as $query) {
            $this->service->handleQuery($query);
        }

        $topQueries = $this->service->getTopSlowestQueries(10);
        $this->assertEquals(3, count($topQueries));
        $this->assertEquals(100, $topQueries[0]['max_time']);
        $this->assertEquals(75, $topQueries[1]['max_time']);
        $this->assertEquals(30, $topQueries[2]['max_time']);
    }

    /**
     * Test that top slowest queries respects limit
     */
    public function test_top_slowest_queries_respects_limit(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $query = (object) [
                'sql' => "SELECT * FROM table_$i",
                'bindings' => [],
                'time' => 50 + $i,
            ];
            $this->service->handleQuery($query);
        }

        $topQueries = $this->service->getTopSlowestQueries(5);
        $this->assertEquals(5, count($topQueries));
    }

    /**
     * Test that statistics summary is generated
     */
    public function test_statistics_summary_is_generated(): void
    {
        $queries = [
            (object) ['sql' => 'SELECT * FROM users', 'bindings' => [], 'time' => 20],
            (object) ['sql' => 'SELECT * FROM users', 'bindings' => [], 'time' => 30],
            (object) ['sql' => 'SELECT * FROM posts', 'bindings' => [], 'time' => 75],
        ];

        foreach ($queries as $query) {
            $this->service->handleQuery($query);
        }

        $summary = $this->service->getStatsSummary();

        $this->assertArrayHasKey('total_queries', $summary);
        $this->assertArrayHasKey('total_execution_time_ms', $summary);
        $this->assertArrayHasKey('average_execution_time_ms', $summary);
        $this->assertArrayHasKey('max_execution_time_ms', $summary);
        $this->assertArrayHasKey('slow_queries_count', $summary);
        $this->assertArrayHasKey('unique_queries', $summary);

        $this->assertEquals(3, $summary['total_queries']);
        $this->assertEquals(2, $summary['unique_queries']);
        $this->assertEquals(1, $summary['slow_queries_count']);
    }

    /**
     * Test that statistics can be cleared
     */
    public function test_statistics_can_be_cleared(): void
    {
        $query = (object) [
            'sql' => 'SELECT * FROM users',
            'bindings' => [],
            'time' => 25,
        ];

        $this->service->handleQuery($query);
        $this->assertNotEmpty($this->service->getAllQueryStats());

        $this->service->clearStats();
        $this->assertEmpty($this->service->getAllQueryStats());
    }

    /**
     * Test that performance degradation is detected
     */
    public function test_performance_degradation_is_detected(): void
    {
        // Simulate a query with varying execution times
        $query = (object) [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [1],
            'time' => 10,
        ];

        $this->service->handleQuery($query);

        // Simulate a slow execution
        $slowQuery = (object) [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [1],
            'time' => 100,
        ];

        $this->service->handleQuery($slowQuery);

        $degraded = $this->service->checkPerformanceDegradation(50);
        $this->assertNotEmpty($degraded);
        $this->assertGreaterThan(50, $degraded[0]['degradation_percent']);
    }

    /**
     * Test that empty statistics summary is returned when no queries
     */
    public function test_empty_summary_when_no_queries(): void
    {
        $summary = $this->service->getStatsSummary();

        $this->assertEquals(0, $summary['total_queries']);
        $this->assertEquals(0, $summary['total_execution_time_ms']);
        $this->assertEquals(0, $summary['average_execution_time_ms']);
        $this->assertEquals(0, $summary['max_execution_time_ms']);
        $this->assertEquals(0, $summary['slow_queries_count']);
    }

    /**
     * Test that different queries are tracked separately
     */
    public function test_different_queries_tracked_separately(): void
    {
        $query1 = (object) [
            'sql' => 'SELECT * FROM users',
            'bindings' => [],
            'time' => 20,
        ];

        $query2 = (object) [
            'sql' => 'SELECT * FROM posts',
            'bindings' => [],
            'time' => 30,
        ];

        $this->service->handleQuery($query1);
        $this->service->handleQuery($query2);

        $stats = $this->service->getAllQueryStats();
        $this->assertEquals(2, count($stats));
    }

    /**
     * Test that query normalization works (extra whitespace)
     */
    public function test_query_normalization_handles_whitespace(): void
    {
        $query1 = (object) [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [1],
            'time' => 20,
        ];

        $query2 = (object) [
            'sql' => 'SELECT  *  FROM  users  WHERE  id  =  ?',
            'bindings' => [1],
            'time' => 30,
        ];

        $this->service->handleQuery($query1);
        $this->service->handleQuery($query2);

        $stats = $this->service->getAllQueryStats();
        // Should be treated as the same query
        $this->assertEquals(1, count($stats));
        $this->assertEquals(2, $stats[0]['count']);
    }

    /**
     * Test that last_executed_at timestamp is tracked
     */
    public function test_last_executed_at_timestamp_is_tracked(): void
    {
        $query = (object) [
            'sql' => 'SELECT * FROM users',
            'bindings' => [],
            'time' => 25,
        ];

        $this->service->handleQuery($query);

        $stats = $this->service->getAllQueryStats();
        $this->assertArrayHasKey('last_executed_at', $stats[0]);
        $this->assertNotEmpty($stats[0]['last_executed_at']);
    }

    /**
     * Test that get query stats returns null for non-existent query
     */
    public function test_get_query_stats_returns_null_for_non_existent(): void
    {
        $stats = $this->service->getQueryStats('SELECT * FROM non_existent_table');
        $this->assertNull($stats);
    }

    /**
     * Test that get query stats returns correct data for existing query
     */
    public function test_get_query_stats_returns_correct_data(): void
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $query = (object) [
            'sql' => $sql,
            'bindings' => [1],
            'time' => 25,
        ];

        $this->service->handleQuery($query);

        $stats = $this->service->getQueryStats($sql);
        $this->assertNotNull($stats);
        $this->assertEquals(1, $stats['count']);
        $this->assertEquals(25, $stats['avg_time']);
    }
}
