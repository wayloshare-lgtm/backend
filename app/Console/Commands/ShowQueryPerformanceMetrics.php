<?php

namespace App\Console\Commands;

use App\Services\QueryPerformanceMonitoringService;
use Illuminate\Console\Command;

class ShowQueryPerformanceMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'query:metrics {--top=10 : Number of slowest queries to show} {--summary : Show only summary statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display query performance metrics and statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $service = new QueryPerformanceMonitoringService();

        if ($this->option('summary')) {
            $this->showSummary($service);
        } else {
            $this->showDetailedMetrics($service);
        }

        return self::SUCCESS;
    }

    /**
     * Show summary statistics
     */
    private function showSummary(QueryPerformanceMonitoringService $service): void
    {
        $summary = $service->getStatsSummary();

        $this->info('Query Performance Summary');
        $this->line('─────────────────────────────────────────');

        $this->line("Total Queries: {$summary['total_queries']}");
        $this->line("Unique Queries: {$summary['unique_queries']}");
        $this->line("Total Execution Time: {$summary['total_execution_time_ms']}ms");
        $this->line("Average Execution Time: {$summary['average_execution_time_ms']}ms");
        $this->line("Max Execution Time: {$summary['max_execution_time_ms']}ms");
        $this->line("Slow Queries (>{$service::getSlowQueryThreshold()}ms): {$summary['slow_queries_count']}");
    }

    /**
     * Show detailed metrics
     */
    private function showDetailedMetrics(QueryPerformanceMonitoringService $service): void
    {
        $limit = (int) $this->option('top');
        $topQueries = $service->getTopSlowestQueries($limit);

        if (empty($topQueries)) {
            $this->info('No query statistics available yet.');
            return;
        }

        $this->info("Top {$limit} Slowest Queries");
        $this->line('─────────────────────────────────────────');

        $headers = ['Query', 'Count', 'Avg (ms)', 'Max (ms)', 'Min (ms)'];
        $rows = [];

        foreach ($topQueries as $query) {
            $rows[] = [
                substr($query['query'], 0, 50) . (strlen($query['query']) > 50 ? '...' : ''),
                $query['count'],
                round($query['avg_time'], 2),
                round($query['max_time'], 2),
                round($query['min_time'], 2),
            ];
        }

        $this->table($headers, $rows);

        // Show summary
        $this->line('');
        $this->showSummary($service);

        // Show slow queries
        $slowQueries = $service->getSlowQueries();
        if (!empty($slowQueries)) {
            $this->line('');
            $this->warn('Slow Queries (exceeding threshold):');
            $this->line('─────────────────────────────────────────');

            foreach ($slowQueries as $query) {
                $this->line("• {$query['query']}");
                $this->line("  Avg: {$query['avg_time']}ms | Max: {$query['max_time']}ms | Count: {$query['count']}");
            }
        }
    }
}
