# Query Performance Monitoring

## Overview

The Query Performance Monitoring system tracks and logs database query execution times, identifies slow queries, and provides metrics for performance analysis. This helps identify performance bottlenecks and optimize database queries.

## Features

- **Automatic Query Tracking**: All database queries are automatically monitored
- **Slow Query Detection**: Queries exceeding 50ms threshold are logged
- **Query Statistics**: Tracks count, average time, max time, and min time for each query
- **Performance Metrics**: Provides endpoints to view performance data
- **Performance Degradation Detection**: Identifies queries with inconsistent performance
- **Console Commands**: CLI tools for viewing metrics
- **Cache-based Storage**: Uses Laravel cache for efficient statistics storage

## Performance Thresholds

- **Slow Query Threshold**: 50ms
- **API Response Time Target**: <200ms
- **Database Query Time Target**: <50ms

## Architecture

### Components

1. **QueryPerformanceMonitoringService**: Core service for monitoring and tracking queries
2. **PerformanceMetricsController**: API endpoints for accessing metrics
3. **ShowQueryPerformanceMetrics**: Console command for viewing metrics
4. **Logging**: Queries logged to `storage/logs/queries.log`

### Initialization

Query monitoring is automatically initialized in the `AppServiceProvider::boot()` method:

```php
QueryPerformanceMonitoringService::initialize();
```

This registers a listener on all database queries.

## API Endpoints

All endpoints require authentication via Sanctum token.

### Get Performance Summary

```
GET /api/v1/admin/performance/summary
```

Returns overall performance statistics.

**Response:**
```json
{
  "success": true,
  "data": {
    "total_queries": 150,
    "total_execution_time_ms": 3500.50,
    "average_execution_time_ms": 23.34,
    "max_execution_time_ms": 125.50,
    "slow_queries_count": 5,
    "unique_queries": 42
  },
  "timestamp": "2024-03-30T12:00:00Z"
}
```

### Get All Query Statistics

```
GET /api/v1/admin/performance/queries
```

Returns statistics for all tracked queries.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "query": "SELECT * FROM users WHERE id = ?",
      "count": 25,
      "total_time": 500.50,
      "avg_time": 20.02,
      "max_time": 45.50,
      "min_time": 15.20,
      "last_executed_at": "2024-03-30T12:00:00Z"
    }
  ],
  "count": 42,
  "timestamp": "2024-03-30T12:00:00Z"
}
```

### Get Slow Queries

```
GET /api/v1/admin/performance/slow-queries
```

Returns only queries exceeding the 50ms threshold.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "query": "SELECT * FROM rides WHERE status = ?",
      "count": 5,
      "avg_time": 75.50,
      "max_time": 125.50,
      "min_time": 60.20
    }
  ],
  "count": 5,
  "threshold_ms": 50,
  "timestamp": "2024-03-30T12:00:00Z"
}
```

### Get Top Slowest Queries

```
GET /api/v1/admin/performance/top-slowest?limit=10
```

Returns the N slowest queries by average execution time.

**Query Parameters:**
- `limit` (optional): Number of queries to return (default: 10)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "query": "SELECT * FROM rides JOIN bookings...",
      "count": 3,
      "avg_time": 120.50,
      "max_time": 150.20,
      "min_time": 100.30
    }
  ],
  "count": 10,
  "limit": 10,
  "timestamp": "2024-03-30T12:00:00Z"
}
```

### Check Performance Degradation

```
GET /api/v1/admin/performance/degradation?threshold=20
```

Returns queries with performance degradation (max time significantly higher than average).

**Query Parameters:**
- `threshold` (optional): Degradation percentage threshold (default: 20)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "query": "SELECT * FROM users WHERE id = ?",
      "average_time_ms": 20.50,
      "max_time_ms": 150.20,
      "degradation_percent": 632.20,
      "execution_count": 50
    }
  ],
  "count": 3,
  "degradation_threshold_percent": 20,
  "timestamp": "2024-03-30T12:00:00Z"
}
```

### Clear Statistics

```
POST /api/v1/admin/performance/clear-stats
```

Clears all collected query statistics.

**Response:**
```json
{
  "success": true,
  "message": "Query statistics cleared successfully",
  "timestamp": "2024-03-30T12:00:00Z"
}
```

## Console Commands

### View Performance Metrics

```bash
php artisan query:metrics
```

Displays top 10 slowest queries with summary statistics.

**Options:**
- `--top=N`: Number of slowest queries to show (default: 10)
- `--summary`: Show only summary statistics

**Examples:**

```bash
# Show top 20 slowest queries
php artisan query:metrics --top=20

# Show only summary
php artisan query:metrics --summary
```

## Logging

Slow queries are logged to `storage/logs/queries.log` with the following information:

```
[2024-03-30 12:00:00] local.WARNING: Slow Query Detected {"sql":"SELECT * FROM users WHERE id = ?","bindings":[1],"execution_time_ms":75.50,"threshold_ms":50,"timestamp":"2024-03-30T12:00:00Z"}
```

## Configuration

### Logging Channel

The queries logging channel is configured in `config/logging.php`:

```php
'queries' => [
    'driver' => 'daily',
    'path' => storage_path('logs/queries.log'),
    'level' => env('LOG_QUERIES_LEVEL', 'warning'),
    'days' => env('LOG_QUERIES_DAYS', 30),
    'replace_placeholders' => true,
],
```

### Environment Variables

```env
LOG_QUERIES_LEVEL=warning    # Logging level for queries
LOG_QUERIES_DAYS=30          # Number of days to keep query logs
```

## Usage Examples

### PHP Service Usage

```php
use App\Services\QueryPerformanceMonitoringService;

$service = new QueryPerformanceMonitoringService();

// Get all statistics
$stats = $service->getAllQueryStats();

// Get slow queries
$slowQueries = $service->getSlowQueries();

// Get top 5 slowest queries
$topSlowest = $service->getTopSlowestQueries(5);

// Get summary
$summary = $service->getStatsSummary();

// Check for degradation
$degraded = $service->checkPerformanceDegradation(20);

// Clear statistics
$service->clearStats();
```

### API Usage

```bash
# Get performance summary
curl -H "Authorization: Bearer TOKEN" \
  https://api.example.com/api/v1/admin/performance/summary

# Get slow queries
curl -H "Authorization: Bearer TOKEN" \
  https://api.example.com/api/v1/admin/performance/slow-queries

# Get top 5 slowest queries
curl -H "Authorization: Bearer TOKEN" \
  https://api.example.com/api/v1/admin/performance/top-slowest?limit=5

# Clear statistics
curl -X POST -H "Authorization: Bearer TOKEN" \
  https://api.example.com/api/v1/admin/performance/clear-stats
```

## Performance Optimization Tips

1. **Identify Slow Queries**: Use the monitoring endpoints to find slow queries
2. **Add Indexes**: Create database indexes on frequently queried columns
3. **Optimize Queries**: Rewrite complex queries to be more efficient
4. **Use Eager Loading**: Load relationships eagerly to avoid N+1 queries
5. **Cache Results**: Cache frequently accessed data
6. **Monitor Degradation**: Watch for queries with inconsistent performance

## Integration with Existing Infrastructure

The query performance monitoring integrates with:

- **RequestLoggingService**: Complements request-level logging with query-level details
- **Logging System**: Uses Laravel's logging infrastructure
- **Cache System**: Stores statistics in Laravel cache
- **Database**: Monitors all database queries automatically

## Testing

The implementation includes comprehensive tests:

- **Unit Tests**: `tests/Unit/QueryPerformanceMonitoringServiceTest.php` (18 tests)
- **Feature Tests**: `tests/Feature/PerformanceMetricsControllerTest.php` (10 tests)

Run tests:
```bash
php artisan test tests/Unit/QueryPerformanceMonitoringServiceTest.php
php artisan test tests/Feature/PerformanceMetricsControllerTest.php
```

## Troubleshooting

### No Query Statistics Appearing

1. Ensure monitoring is initialized in `AppServiceProvider`
2. Check that cache is properly configured
3. Verify database queries are being executed
4. Check `storage/logs/queries.log` for errors

### High Memory Usage

1. Clear statistics regularly using the clear-stats endpoint
2. Reduce the cache duration in the service
3. Monitor the number of unique queries being tracked

### Slow Query Threshold Not Working

1. Verify the threshold is set to 50ms in the service
2. Check that queries are actually exceeding the threshold
3. Ensure logging is enabled in configuration

## Future Enhancements

- Real-time query monitoring dashboard
- Query performance alerts
- Historical performance trends
- Query optimization suggestions
- Integration with APM tools
- Query plan analysis
