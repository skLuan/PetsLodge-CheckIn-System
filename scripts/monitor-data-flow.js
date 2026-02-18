/**
 * Data Flow Monitoring Script
 * Monitors cookie changes, data transformations, and system health
 * Usage: node scripts/monitor-data-flow.js
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Configuration
const CONFIG = {
    logDir: './logs/monitoring',
    reportDir: './reports/monitoring',
    checkInterval: 5000, // 5 seconds
    alertThreshold: {
        errorRate: 0.05, // 5%
        transformationFailure: 0.1, // 10%
        responseTime: 5000, // 5 seconds
    },
    metricsWindow: 3600000, // 1 hour
};

// Metrics storage
const metrics = {
    cookieChanges: [],
    dataTransformations: [],
    formSubmissions: [],
    errors: [],
    performanceMetrics: [],
};

// Initialize directories
function initializeDirs() {
    [CONFIG.logDir, CONFIG.reportDir].forEach(dir => {
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
        }
    });
}

// Log event
function logEvent(type, data) {
    const timestamp = new Date().toISOString();
    const logEntry = {
        timestamp,
        type,
        data,
    };

    // Store in memory
    if (type === 'cookie_change') {
        metrics.cookieChanges.push(logEntry);
    } else if (type === 'data_transformation') {
        metrics.dataTransformations.push(logEntry);
    } else if (type === 'form_submission') {
        metrics.formSubmissions.push(logEntry);
    } else if (type === 'error') {
        metrics.errors.push(logEntry);
    } else if (type === 'performance') {
        metrics.performanceMetrics.push(logEntry);
    }

    // Write to file
    const logFile = path.join(CONFIG.logDir, `${type}-${new Date().toISOString().split('T')[0]}.log`);
    fs.appendFileSync(logFile, JSON.stringify(logEntry) + '\n');

    console.log(`[${timestamp}] ${type.toUpperCase()}: ${JSON.stringify(data)}`);
}

// Monitor cookie changes
function monitorCookieChanges() {
    // Simulated cookie monitoring
    // In production, this would integrate with actual cookie tracking
    const cookieChangeEvent = {
        name: 'checkInData',
        action: 'update',
        timestamp: new Date().toISOString(),
    };

    logEvent('cookie_change', cookieChangeEvent);
}

// Monitor data transformations
function monitorDataTransformations() {
    // Simulated data transformation monitoring
    const transformationEvent = {
        service: 'CheckInTransformer',
        status: 'success',
        duration: Math.random() * 1000,
        recordsProcessed: Math.floor(Math.random() * 100),
    };

    logEvent('data_transformation', transformationEvent);
}

// Monitor form submissions
function monitorFormSubmissions() {
    // Simulated form submission monitoring
    const submissionEvent = {
        form: 'checkInForm',
        status: Math.random() > 0.05 ? 'success' : 'failed',
        duration: Math.random() * 2000,
        timestamp: new Date().toISOString(),
    };

    logEvent('form_submission', submissionEvent);
}

// Check for data inconsistencies
function checkDataConsistency() {
    const recentTransformations = metrics.dataTransformations.filter(
        t => new Date(t.timestamp) > new Date(Date.now() - CONFIG.metricsWindow)
    );

    if (recentTransformations.length === 0) {
        return;
    }

    const failedCount = recentTransformations.filter(t => t.data.status === 'failed').length;
    const failureRate = failedCount / recentTransformations.length;

    if (failureRate > CONFIG.alertThreshold.transformationFailure) {
        logEvent('error', {
            type: 'high_transformation_failure_rate',
            rate: failureRate,
            threshold: CONFIG.alertThreshold.transformationFailure,
            message: `Data transformation failure rate (${(failureRate * 100).toFixed(2)}%) exceeds threshold`,
        });
    }
}

// Check error rate
function checkErrorRate() {
    const recentErrors = metrics.errors.filter(
        e => new Date(e.timestamp) > new Date(Date.now() - CONFIG.metricsWindow)
    );

    const recentSubmissions = metrics.formSubmissions.filter(
        s => new Date(s.timestamp) > new Date(Date.now() - CONFIG.metricsWindow)
    );

    if (recentSubmissions.length === 0) {
        return;
    }

    const errorRate = recentErrors.length / recentSubmissions.length;

    if (errorRate > CONFIG.alertThreshold.errorRate) {
        logEvent('error', {
            type: 'high_error_rate',
            rate: errorRate,
            threshold: CONFIG.alertThreshold.errorRate,
            message: `Error rate (${(errorRate * 100).toFixed(2)}%) exceeds threshold`,
        });
    }
}

// Check performance metrics
function checkPerformance() {
    const recentMetrics = metrics.performanceMetrics.filter(
        m => new Date(m.timestamp) > new Date(Date.now() - CONFIG.metricsWindow)
    );

    if (recentMetrics.length === 0) {
        return;
    }

    const avgResponseTime = recentMetrics.reduce((sum, m) => sum + m.data.duration, 0) / recentMetrics.length;

    if (avgResponseTime > CONFIG.alertThreshold.responseTime) {
        logEvent('error', {
            type: 'performance_degradation',
            avgResponseTime,
            threshold: CONFIG.alertThreshold.responseTime,
            message: `Average response time (${avgResponseTime.toFixed(2)}ms) exceeds threshold`,
        });
    }
}

// Generate monitoring report
function generateReport() {
    const now = new Date();
    const reportData = {
        timestamp: now.toISOString(),
        period: {
            start: new Date(now - CONFIG.metricsWindow).toISOString(),
            end: now.toISOString(),
        },
        summary: {
            totalCookieChanges: metrics.cookieChanges.length,
            totalDataTransformations: metrics.dataTransformations.length,
            totalFormSubmissions: metrics.formSubmissions.length,
            totalErrors: metrics.errors.length,
        },
        metrics: {
            cookieChangeRate: (metrics.cookieChanges.length / (CONFIG.metricsWindow / 1000)).toFixed(2),
            transformationSuccessRate: calculateSuccessRate(metrics.dataTransformations),
            formSubmissionSuccessRate: calculateSuccessRate(metrics.formSubmissions),
            errorRate: (metrics.errors.length / (metrics.formSubmissions.length || 1)).toFixed(4),
        },
        recentErrors: metrics.errors.slice(-10),
        alerts: generateAlerts(),
    };

    const reportFile = path.join(
        CONFIG.reportDir,
        `monitoring-report-${now.toISOString().replace(/[:.]/g, '-')}.json`
    );

    fs.writeFileSync(reportFile, JSON.stringify(reportData, null, 2));
    console.log(`\n📊 Report generated: ${reportFile}`);

    return reportData;
}

// Calculate success rate
function calculateSuccessRate(events) {
    if (events.length === 0) return 1.0;
    const successCount = events.filter(e => e.data.status === 'success').length;
    return (successCount / events.length).toFixed(4);
}

// Generate alerts
function generateAlerts() {
    const alerts = [];

    // Check for high error rate
    const recentErrors = metrics.errors.filter(
        e => new Date(e.timestamp) > new Date(Date.now() - CONFIG.metricsWindow)
    );

    if (recentErrors.length > 0) {
        alerts.push({
            level: 'warning',
            type: 'errors_detected',
            count: recentErrors.length,
            message: `${recentErrors.length} errors detected in the last hour`,
        });
    }

    // Check for low transformation success rate
    const recentTransformations = metrics.dataTransformations.filter(
        t => new Date(t.timestamp) > new Date(Date.now() - CONFIG.metricsWindow)
    );

    if (recentTransformations.length > 0) {
        const failureRate = recentTransformations.filter(t => t.data.status === 'failed').length / recentTransformations.length;
        if (failureRate > 0.05) {
            alerts.push({
                level: 'critical',
                type: 'transformation_failures',
                rate: failureRate,
                message: `Data transformation failure rate is ${(failureRate * 100).toFixed(2)}%`,
            });
        }
    }

    return alerts;
}

// Start monitoring
function startMonitoring() {
    console.log('🚀 Starting Data Flow Monitoring...');
    console.log(`📁 Logs directory: ${CONFIG.logDir}`);
    console.log(`📊 Reports directory: ${CONFIG.reportDir}`);
    console.log('');

    initializeDirs();

    // Initial report
    logEvent('system', {
        message: 'Monitoring system started',
        config: CONFIG,
    });

    // Set up monitoring intervals
    setInterval(() => {
        monitorCookieChanges();
        monitorDataTransformations();
        monitorFormSubmissions();
        checkDataConsistency();
        checkErrorRate();
        checkPerformance();
    }, CONFIG.checkInterval);

    // Generate reports every hour
    setInterval(() => {
        generateReport();
    }, CONFIG.metricsWindow);

    // Generate initial report after 1 minute
    setTimeout(() => {
        generateReport();
    }, 60000);

    console.log('✅ Monitoring system is running');
    console.log(`📈 Check interval: ${CONFIG.checkInterval}ms`);
    console.log(`📊 Report interval: ${CONFIG.metricsWindow}ms`);
}

// Handle graceful shutdown
process.on('SIGINT', () => {
    console.log('\n\n🛑 Shutting down monitoring system...');
    generateReport();
    console.log('✅ Final report generated');
    process.exit(0);
});

// Start the monitoring system
startMonitoring();
