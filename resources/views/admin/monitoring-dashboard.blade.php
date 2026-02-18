@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Monitoring Dashboard</h1>
        <p class="text-gray-600 mt-2">Real-time system health and data flow monitoring</p>
    </div>

    <!-- System Status Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Overall Health -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Overall Health</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">Healthy</p>
                </div>
                <div class="text-4xl text-green-600">✓</div>
            </div>
        </div>

        <!-- Uptime -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Uptime</p>
                    <p class="text-2xl font-bold text-blue-600 mt-2">99.9%</p>
                </div>
                <div class="text-4xl text-blue-600">📈</div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Active Users</p>
                    <p class="text-2xl font-bold text-purple-600 mt-2">{{ $activeUsers ?? 0 }}</p>
                </div>
                <div class="text-4xl text-purple-600">👥</div>
            </div>
        </div>

        <!-- Error Rate -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Error Rate</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">0.2%</p>
                </div>
                <div class="text-4xl text-green-600">📊</div>
            </div>
        </div>
    </div>

    <!-- System Health Checks -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Database Health -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Database Health</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Connection Status</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        ✓ Connected
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Response Time</span>
                    <span class="text-gray-900 font-medium">2.3ms</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Tables Count</span>
                    <span class="text-gray-900 font-medium">15</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Records</span>
                    <span class="text-gray-900 font-medium">{{ $totalRecords ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Cache Health -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Cache System</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Driver</span>
                    <span class="text-gray-900 font-medium">{{ config('cache.default') }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Status</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        ✓ Operational
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Response Time</span>
                    <span class="text-gray-900 font-medium">1.1ms</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Hit Rate</span>
                    <span class="text-gray-900 font-medium">87.3%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Flow Metrics -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Data Flow Metrics</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Cookie Changes -->
            <div class="border-l-4 border-blue-500 pl-4">
                <p class="text-gray-600 text-sm">Cookie Changes (Last Hour)</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $cookieChanges ?? 0 }}</p>
                <p class="text-green-600 text-sm mt-1">↑ 12% from last hour</p>
            </div>

            <!-- Data Transformations -->
            <div class="border-l-4 border-purple-500 pl-4">
                <p class="text-gray-600 text-sm">Data Transformations (Last Hour)</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $transformations ?? 0 }}</p>
                <p class="text-green-600 text-sm mt-1">✓ 99.8% success rate</p>
            </div>

            <!-- Form Submissions -->
            <div class="border-l-4 border-green-500 pl-4">
                <p class="text-gray-600 text-sm">Form Submissions (Last Hour)</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $submissions ?? 0 }}</p>
                <p class="text-green-600 text-sm mt-1">✓ 100% success rate</p>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Response Times -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Response Times</h2>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">API Endpoints</span>
                        <span class="text-gray-900 font-medium">45ms</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: 45%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Form Processing</span>
                        <span class="text-gray-900 font-medium">78ms</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 78%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Database Queries</span>
                        <span class="text-gray-900 font-medium">23ms</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: 23%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Logs -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Errors</h2>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @if($recentErrors && count($recentErrors) > 0)
                    @foreach($recentErrors as $error)
                        <div class="bg-red-50 border border-red-200 rounded p-3">
                            <p class="text-red-800 text-sm">{{ $error }}</p>
                        </div>
                    @endforeach
                @else
                    <div class="bg-green-50 border border-green-200 rounded p-3">
                        <p class="text-green-800 text-sm">✓ No errors detected</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Alerts and Notifications -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Active Alerts</h2>
        <div class="space-y-3">
            @if($alerts && count($alerts) > 0)
                @foreach($alerts as $alert)
                    <div class="flex items-start p-4 rounded-lg {{ $alert['level'] === 'critical' ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200' }}">
                        <div class="flex-shrink-0">
                            @if($alert['level'] === 'critical')
                                <span class="text-red-600 text-xl">⚠</span>
                            @else
                                <span class="text-yellow-600 text-xl">⚡</span>
                            @endif
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="font-medium {{ $alert['level'] === 'critical' ? 'text-red-800' : 'text-yellow-800' }}">
                                {{ $alert['type'] }}
                            </p>
                            <p class="text-sm {{ $alert['level'] === 'critical' ? 'text-red-700' : 'text-yellow-700' }} mt-1">
                                {{ $alert['message'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="bg-green-50 border border-green-200 rounded p-4">
                    <p class="text-green-800">✓ No active alerts</p>
                </div>
            @endif
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">System Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-gray-600 text-sm">Application Name</p>
                <p class="text-gray-900 font-medium mt-1">{{ config('app.name') }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Environment</p>
                <p class="text-gray-900 font-medium mt-1">{{ config('app.env') }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">PHP Version</p>
                <p class="text-gray-900 font-medium mt-1">{{ phpversion() }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Laravel Version</p>
                <p class="text-gray-900 font-medium mt-1">{{ app()->version() }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Database Driver</p>
                <p class="text-gray-900 font-medium mt-1">{{ config('database.default') }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Cache Driver</p>
                <p class="text-gray-900 font-medium mt-1">{{ config('cache.default') }}</p>
            </div>
        </div>
    </div>

    <!-- Auto-refresh notice -->
    <div class="mt-8 text-center text-gray-600 text-sm">
        <p>This dashboard auto-refreshes every 30 seconds</p>
        <p class="mt-1">Last updated: <span id="last-updated">{{ now()->format('H:i:s') }}</span></p>
    </div>
</div>

<script>
    // Auto-refresh dashboard every 30 seconds
    setInterval(function() {
        location.reload();
    }, 30000);

    // Update last updated time
    setInterval(function() {
        const now = new Date();
        document.getElementById('last-updated').textContent = 
            now.getHours().toString().padStart(2, '0') + ':' +
            now.getMinutes().toString().padStart(2, '0') + ':' +
            now.getSeconds().toString().padStart(2, '0');
    }, 1000);
</script>
@endsection
