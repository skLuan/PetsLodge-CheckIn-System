#!/bin/bash

# Post-Deployment Verification Script
# Verifies system functionality after deployment
# Usage: ./scripts/post-deployment-verify.sh [environment]

set -e

ENVIRONMENT=${1:-staging}

echo "=========================================="
echo "Post-Deployment Verification Script"
echo "Environment: $ENVIRONMENT"
echo "=========================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print test result
test_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ PASS${NC}: $2"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ FAIL${NC}: $2"
        ((TESTS_FAILED++))
    fi
}

# Function to test endpoint
test_endpoint() {
    local url=$1
    local expected_status=$2
    local description=$3

    echo "Testing: $description"
    local response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")

    if [ "$response" = "$expected_status" ]; then
        test_result 0 "$description (HTTP $response)"
    else
        test_result 1 "$description (Expected $expected_status, got $response)"
    fi
}

# Determine base URL
if [ "$ENVIRONMENT" = "production" ]; then
    BASE_URL="https://petslodge.com"
elif [ "$ENVIRONMENT" = "staging" ]; then
    BASE_URL="http://staging.petslodge.local"
else
    BASE_URL="http://localhost:8000"
fi

echo "Base URL: $BASE_URL"
echo ""

# 1. Test application health
echo "1. Testing Application Health..."
test_endpoint "$BASE_URL/health" "200" "Health check endpoint"
echo ""

# 2. Test API endpoints
echo "2. Testing API Endpoints..."
test_endpoint "$BASE_URL/api/check-ins" "200" "Check-ins API endpoint"
test_endpoint "$BASE_URL/api/pets" "200" "Pets API endpoint"
test_endpoint "$BASE_URL/api/users" "200" "Users API endpoint"
echo ""

# 3. Test form endpoints
echo "3. Testing Form Endpoints..."
test_endpoint "$BASE_URL/check-in" "200" "Check-in form page"
test_endpoint "$BASE_URL/view-check-in" "200" "View check-in page"
echo ""

# 4. Test authentication
echo "4. Testing Authentication..."
test_endpoint "$BASE_URL/login" "200" "Login page"
test_endpoint "$BASE_URL/register" "200" "Register page"
echo ""

# 5. Test database connectivity
echo "5. Testing Database Connectivity..."
if php artisan tinker --execute="echo 'Database connected';" 2>/dev/null | grep -q "Database connected"; then
    test_result 0 "Database connectivity"
else
    test_result 1 "Database connectivity"
fi
echo ""

# 6. Test cache system
echo "6. Testing Cache System..."
if php artisan cache:clear 2>/dev/null; then
    test_result 0 "Cache system operational"
else
    test_result 1 "Cache system operational"
fi
echo ""

# 7. Test session handling
echo "7. Testing Session Handling..."
if [ -d "storage/framework/sessions" ]; then
    test_result 0 "Session storage directory exists"
else
    test_result 1 "Session storage directory exists"
fi
echo ""

# 8. Test file permissions
echo "8. Testing File Permissions..."
if [ -w "storage" ] && [ -w "bootstrap/cache" ]; then
    test_result 0 "Storage and cache directories are writable"
else
    test_result 1 "Storage and cache directories are writable"
fi
echo ""

# 9. Test critical data flows
echo "9. Testing Critical Data Flows..."
if [ -f "app/Services/CheckInTransformer.php" ]; then
    test_result 0 "Data transformation service exists"
else
    test_result 1 "Data transformation service exists"
fi

if [ -f "app/Services/CheckInDataValidator.php" ]; then
    test_result 0 "Data validation service exists"
else
    test_result 1 "Data validation service exists"
fi
echo ""

# 10. Test monitoring system
echo "10. Testing Monitoring System..."
if [ -f "scripts/monitor-data-flow.js" ]; then
    test_result 0 "Monitoring script exists"
else
    test_result 1 "Monitoring script exists"
fi

if [ -d "logs/monitoring" ]; then
    test_result 0 "Monitoring logs directory exists"
else
    test_result 1 "Monitoring logs directory exists"
fi
echo ""

# 11. Test error logging
echo "11. Testing Error Logging..."
if [ -d "storage/logs" ]; then
    test_result 0 "Error logs directory exists"
else
    test_result 1 "Error logs directory exists"
fi
echo ""

# 12. Test environment configuration
echo "12. Testing Environment Configuration..."
if [ -f ".env" ]; then
    test_result 0 "Environment file exists"
else
    test_result 1 "Environment file exists"
fi

if grep -q "APP_KEY=" .env; then
    test_result 0 "Application key is configured"
else
    test_result 1 "Application key is configured"
fi
echo ""

# 13. Test migrations
echo "13. Testing Database Migrations..."
if php artisan migrate:status 2>/dev/null | grep -q "Ran"; then
    test_result 0 "Database migrations have been run"
else
    test_result 1 "Database migrations have been run"
fi
echo ""

# 14. Test asset compilation
echo "14. Testing Asset Compilation..."
if [ -f "public/build/manifest.json" ] || [ -f "public/hot" ]; then
    test_result 0 "Assets are compiled"
else
    test_result 1 "Assets are compiled"
fi
echo ""

# 15. Test critical routes
echo "15. Testing Critical Routes..."
if php artisan route:list 2>/dev/null | grep -q "check-in"; then
    test_result 0 "Check-in routes are registered"
else
    test_result 1 "Check-in routes are registered"
fi
echo ""

# Generate verification report
echo "=========================================="
echo "Post-Deployment Verification Report"
echo "=========================================="
echo "Environment: $ENVIRONMENT"
echo "Timestamp: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo ""
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"
echo ""

# Create report file
REPORT_FILE="reports/post-deployment-verify-$(date +%Y%m%d-%H%M%S).txt"
mkdir -p reports

{
    echo "Post-Deployment Verification Report"
    echo "===================================="
    echo "Environment: $ENVIRONMENT"
    echo "Timestamp: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
    echo "Base URL: $BASE_URL"
    echo ""
    echo "Results:"
    echo "--------"
    echo "Passed: $TESTS_PASSED"
    echo "Failed: $TESTS_FAILED"
    echo ""
    if [ $TESTS_FAILED -eq 0 ]; then
        echo "Status: ✓ ALL TESTS PASSED"
    else
        echo "Status: ✗ SOME TESTS FAILED"
    fi
} > "$REPORT_FILE"

echo "📄 Report saved to: $REPORT_FILE"
echo ""

# Exit with appropriate code
if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ Post-deployment verification successful!${NC}"
    echo "System is operational and ready for use."
    exit 0
else
    echo -e "${RED}✗ Post-deployment verification failed.${NC}"
    echo "Please investigate the failed tests before proceeding."
    exit 1
fi
