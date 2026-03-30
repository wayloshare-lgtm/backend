<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\QueryPerformanceMonitoringService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PerformanceMetricsControllerTest extends TestCase
{
    private User $driverUser;
    private User $riderUser;
    private QueryPerformanceMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create driver user (can be treated as admin for testing)
        $this->driverUser = User::factory()->driver()->create();

        // Create rider user (regular user)
        $this->riderUser = User::factory()->create();

        $this->monitoringService = new QueryPerformanceMonitoringService();
        Cache::flush();
    }

    /**
     * Test that performance summary endpoint requires authentication
     */
    public function test_performance_summary_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/performance/summary');

        $response->assertStatus(401);
    }

    /**
     * Test that performance summary endpoint returns correct data
     */
    public function test_performance_summary_returns_correct_data(): void
    {
        // Simulate some queries
        $query = (object) [
            'sql' => 'SELECT * FROM users',
            'bindings' => [],
            'time' => 25,
        ];
        $this->monitoringService->handleQuery($query);

        $response = $this->actingAs($this->driverUser, 'sanctum')
            ->getJson('/api/v1/admin/performance/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_queries',
                    'total_execution_time_ms',
                    'average_execution_time_ms',
                    'max_execution_time_ms',
                    'slow_queries_count',
                ],
                'timestamp',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test that all queries endpoint returns correct data
     */
    public function test_all_queries_endpoint_returns_data(): void
    {
        $query = (object) [
            'sql' => 'SELECT * FROM users',
            'bindings' => [],
            'time' => 25,
        ];
        $this->monitoringService->handleQuery($query);

        $response = $this->actingAs($this->driverUser, 'sanctum')
            ->getJson('/api/v1/admin/performance/queries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'count',
                'timestamp',
            ])
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);
    }

    /**
     * Test that slow queries endpoint returns only slow queries
     */
    public function test_slow_queries_endpoint_returns_only_slow(): void
    {
        $queries = [
            (object) ['sql' => 'SELECT * FROM users', 'bindings' => [], 'time' => 30],
            (object) ['sql' => 'SELECT * FROM posts', 'bindings' => [], 'time' => 75],
        ];

        foreach ($queries as $query) {
            $this->monitoringService->handleQuery($query);
        }

        $response = $this->actingAs($this->driverUser, 'sanctum')
            ->getJson('/api/v1/admin/performance/slow-queries');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);
    }

    /**
     * Test that top slowest endpoint respects limit parameter
     */
    public function test_top_slowest_respects_limit_parameter(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $query = (object) [
                'sql' => "SELECT * FROM table_$i",
                'bindings' => [],
                'time' => 50 + $i,
            ];
            $this->monitoringService->handleQuery($query);
        }

        $response = $this->actingAs($this->driverUser, 'sanctum')
            ->getJson('/api/v1/admin/performance/top-slowest?limit=5');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 5,
                'limit' => 5,
            ]);
    }

    /**
     * Test that degradation endpoint returns degraded queries
     */
    public function test_degradation_endpoint_returns_degraded_queries(): void
    {
        // Simulate a query with varying execution times
        $query = (object) [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [1],
            'time' => 10,
        ];
        $this->monitoringService->handleQuery($query);

        $slowQuery = (object) [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [1],
            'time' => 100,
        ];
        $this->monitoringService->handleQuery($slowQuery);

        $response = $this->actingAs($this->driverUser, 'sanctum')
            ->getJson('/api/v1/admin/performance/degradation?threshold=50');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'count',
                'degradation_threshold_percent',
                'timestamp',
            ]);
    }

    /**
     * Test that clear stats endpoint clears statistics
     */
    public function test_clear_stats_endpoint_clears_statistics(): void
    {
        $query = (object) [
            'sql' => 'SELECT * FROM users',
            'bindings' => [],
            'time' => 25,
        ];
        $this->monitoringService->handleQuery($query);

        // Verify stats exist
        $this->assertNotEmpty($this->monitoringService->getAllQueryStats());

        $response = $this->actingAs($this->driverUser, 'sanctum')
            ->postJson('/api/v1/admin/performance/clear-stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Query statistics cleared successfully',
            ]);

        // Verify stats are cleared
        $this->assertEmpty($this->monitoringService->getAllQueryStats());
    }

    /**
     * Test that performance endpoints return empty data when no queries
     */
    public function test_endpoints_return_empty_data_when_no_queries(): void
    {
        $response = $this->actingAs($this->driverUser, 'sanctum')
            ->getJson('/api/v1/admin/performance/queries');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 0,
            ]);
    }

    /**
     * Test that performance summary includes timestamp
     */
    public function test_performance_summary_includes_timestamp(): void
    {
        $response = $this->actingAs($this->driverUser, 'sanctum')
            ->getJson('/api/v1/admin/performance/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'timestamp',
            ]);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $response->json('timestamp')
        );
    }

    /**
     * Test that top slowest endpoint returns queries in correct order
     */
    public function test_top_slowest_returns_queries_in_order(): void
    {
        $queries = [
            (object) ['sql' => 'SELECT * FROM users', 'bindings' => [], 'time' => 30],
            (object) ['sql' => 'SELECT * FROM posts', 'bindings' => [], 'time' => 75],
            (object) ['sql' => 'SELECT * FROM comments', 'bindings' => [], 'time' => 100],
        ];

        foreach ($queries as $query) {
            $this->monitoringService->handleQuery($query);
        }

        $response = $this->actingAs($this->driverUser, 'sanctum')
            ->getJson('/api/v1/admin/performance/top-slowest?limit=10');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify queries are ordered by max_time descending
        $this->assertEquals(100, $data[0]['max_time']);
        $this->assertEquals(75, $data[1]['max_time']);
        $this->assertEquals(30, $data[2]['max_time']);
    }
}
