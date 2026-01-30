<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends Controller
{
    /**
     * Get system health status
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => [],
            'metrics' => [],
        ];

        // Check database connectivity
        $health['checks']['database'] = $this->checkDatabase();

        // Check cache system
        $health['checks']['cache'] = $this->checkCache();

        // Check file system
        $health['checks']['filesystem'] = $this->checkFilesystem();

        // Check data integrity
        $health['checks']['data_integrity'] = $this->checkDataIntegrity();

        // Get performance metrics
        $health['metrics'] = $this->getMetrics();

        // Determine overall health status
        $health['status'] = $this->determineOverallStatus($health['checks']);

        // Log health check
        Log::info('Health check performed', [
            'status' => $health['status'],
            'checks' => $health['checks'],
        ]);

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Check database connectivity and health
     *
     * @return array
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);

            // Test database connection
            DB::connection()->getPdo();

            $duration = (microtime(true) - $startTime) * 1000;

            // Check if tables exist
            $tables = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [
                config('database.connections.mysql.database'),
            ]);

            return [
                'status' => 'healthy',
                'response_time_ms' => round($duration, 2),
                'tables_count' => $tables[0]->count ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache system health
     *
     * @return array
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';

            $startTime = microtime(true);

            // Test cache write
            Cache::put($testKey, $testValue, 60);

            // Test cache read
            $retrieved = Cache::get($testKey);

            $duration = (microtime(true) - $startTime) * 1000;

            // Clean up
            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => round($duration, 2),
                    'driver' => config('cache.default'),
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Cache read/write mismatch',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Cache health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check file system health
     *
     * @return array
     */
    private function checkFilesystem(): array
    {
        $checks = [
            'storage_writable' => is_writable(storage_path()),
            'bootstrap_cache_writable' => is_writable(base_path('bootstrap/cache')),
            'logs_directory_exists' => is_dir(storage_path('logs')),
        ];

        $allHealthy = array_reduce($checks, function ($carry, $item) {
            return $carry && $item;
        }, true);

        return [
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
        ];
    }

    /**
     * Check data integrity
     *
     * @return array
     */
    private function checkDataIntegrity(): array
    {
        try {
            $checks = [];

            // Check if critical tables have data
            $tables = [
                'users' => 'Users table',
                'check_ins' => 'Check-ins table',
                'pets' => 'Pets table',
            ];

            foreach ($tables as $table => $label) {
                $count = DB::table($table)->count();
                $checks[$table] = [
                    'label' => $label,
                    'record_count' => $count,
                    'status' => $count > 0 ? 'healthy' : 'warning',
                ];
            }

            // Check for orphaned records
            $orphanedPets = DB::table('pets')
                ->whereNotIn('user_id', DB::table('users')->select('id'))
                ->count();

            $checks['orphaned_records'] = [
                'orphaned_pets' => $orphanedPets,
                'status' => $orphanedPets === 0 ? 'healthy' : 'warning',
            ];

            $allHealthy = array_reduce($checks, function ($carry, $item) {
                return $carry && ($item['status'] !== 'unhealthy');
            }, true);

            return [
                'status' => $allHealthy ? 'healthy' : 'warning',
                'checks' => $checks,
            ];
        } catch (\Exception $e) {
            Log::error('Data integrity check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system performance metrics
     *
     * @return array
     */
    private function getMetrics(): array
    {
        return [
            'memory_usage' => [
                'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ],
            'uptime' => $this->getServerUptime(),
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
        ];
    }

    /**
     * Get server uptime
     *
     * @return string
     */
    private function getServerUptime(): string
    {
        if (function_exists('shell_exec')) {
            $uptime = @shell_exec('uptime');
            if ($uptime !== null) {
                return trim($uptime);
            }
        }

        return 'Unknown';
    }

    /**
     * Determine overall health status
     *
     * @param array $checks
     * @return string
     */
    private function determineOverallStatus(array $checks): string
    {
        foreach ($checks as $check) {
            if (isset($check['status']) && $check['status'] === 'unhealthy') {
                return 'unhealthy';
            }
        }

        return 'healthy';
    }

    /**
     * Get detailed health report
     *
     * @return JsonResponse
     */
    public function report(): JsonResponse
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'system' => [
                'app_name' => config('app.name'),
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'url' => config('app.url'),
            ],
            'database' => [
                'driver' => config('database.default'),
                'host' => config('database.connections.mysql.host'),
                'database' => config('database.connections.mysql.database'),
            ],
            'cache' => [
                'driver' => config('cache.default'),
            ],
            'logging' => [
                'channel' => config('logging.default'),
                'level' => config('logging.level'),
            ],
            'recent_errors' => $this->getRecentErrors(),
        ];

        return response()->json($report);
    }

    /**
     * Get recent errors from logs
     *
     * @return array
     */
    private function getRecentErrors(): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return [];
        }

        $lines = array_reverse(file($logFile));
        $errors = [];

        foreach (array_slice($lines, 0, 10) as $line) {
            if (strpos($line, 'ERROR') !== false || strpos($line, 'Exception') !== false) {
                $errors[] = trim($line);
            }
        }

        return $errors;
    }
}
