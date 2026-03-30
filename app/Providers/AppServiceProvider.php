<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Services\QueryPerformanceMonitoringService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Initialize query performance monitoring
        QueryPerformanceMonitoringService::initialize();

        // Configure rate limiters for sensitive endpoints
        RateLimiter::for('auth', function (Request $request) {
            // 5 requests per minute for authentication endpoints
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('sensitive', function (Request $request) {
            // 10 requests per minute for sensitive endpoints
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            // 60 requests per minute for general API endpoints
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
