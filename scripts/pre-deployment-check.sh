#!/bin/bash

# Pre-Deployment Check Script
# Verifies system readiness before deployment
# Usage: ./scripts/pre-deployment-check.sh

set -e

echo "=========================================="
echo "Pre-Deployment Check Script"
echo "=========================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
CHECKS_PASSED=0
CHECKS_FAILED=0

# Function to print check result
check_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ PASS${NC}: $2"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}✗ FAIL${NC}: $2"
        ((CHECKS_FAILED++))
    fi
}

# 1. Check if all code changes are committed
echo "1. Checking git status..."
if [ -z "$(git status --porcelain)" ]; then
    check_result 0 "All code changes are committed"
else
    echo -e "${YELLOW}⚠ WARNING${NC}: Uncommitted changes detected:"
    git status --short
    check_result 1 "All code changes are committed"
fi
echo ""

# 2. Run linting checks
echo "2. Running linting checks..."
if npm run lint 2>/dev/null; then
    check_result 0 "Linting checks passed"
else
    check_result 1 "Linting checks failed"
fi
echo ""

# 3. Verify documentation is complete
echo "3. Verifying documentation..."
REQUIRED_DOCS=(
    "docs/DATA_FLOW.md"
    "docs/DEVELOPER_GUIDE.md"
    "docs/API_REFERENCE.md"
    "docs/DEPLOYMENT_GUIDE.md"
    "docs/MIGRATION_GUIDE.md"
    "README.md"
)

DOCS_MISSING=0
for doc in "${REQUIRED_DOCS[@]}"; do
    if [ -f "$doc" ]; then
        echo "  ✓ Found: $doc"
    else
        echo "  ✗ Missing: $doc"
        ((DOCS_MISSING++))
    fi
done

if [ $DOCS_MISSING -eq 0 ]; then
    check_result 0 "All required documentation exists"
else
    check_result 1 "Some documentation files are missing"
fi
echo ""

# 4. Check for console errors in JavaScript
echo "4. Checking for console errors..."
if grep -r "console\.error" resources/js --include="*.js" 2>/dev/null | grep -v "node_modules" > /dev/null; then
    check_result 1 "Console errors found in JavaScript files"
else
    check_result 0 "No console errors detected"
fi
echo ""

# 5. Verify cookie structure
echo "5. Verifying cookie structure..."
if [ -f "resources/js/cookies-and-form/config.js" ]; then
    if grep -q "COOKIE_NAMES\|COOKIE_CONFIG" resources/js/cookies-and-form/config.js; then
        check_result 0 "Cookie structure is properly defined"
    else
        check_result 1 "Cookie structure is not properly defined"
    fi
else
    check_result 1 "Cookie configuration file not found"
fi
echo ""

# 6. Test data transformations
echo "6. Testing data transformations..."
if [ -f "app/Services/CheckInTransformer.php" ]; then
    if grep -q "transform\|transform" app/Services/CheckInTransformer.php; then
        check_result 0 "Data transformation service exists"
    else
        check_result 1 "Data transformation service is incomplete"
    fi
else
    check_result 1 "Data transformation service not found"
fi
echo ""

# 7. Verify environment configuration
echo "7. Verifying environment configuration..."
if [ -f ".env.example" ]; then
    check_result 0 "Environment configuration template exists"
else
    check_result 1 "Environment configuration template not found"
fi
echo ""

# 8. Check database migrations
echo "8. Checking database migrations..."
if [ -d "database/migrations" ] && [ "$(ls -A database/migrations)" ]; then
    check_result 0 "Database migrations exist"
else
    check_result 1 "No database migrations found"
fi
echo ""

# 9. Verify test suite
echo "9. Verifying test suite..."
if [ -d "tests" ] && [ "$(ls -A tests)" ]; then
    check_result 0 "Test suite exists"
else
    check_result 1 "Test suite not found"
fi
echo ""

# 10. Check for security vulnerabilities
echo "10. Checking for security vulnerabilities..."
if composer audit 2>/dev/null | grep -q "No known security vulnerabilities"; then
    check_result 0 "No known security vulnerabilities"
else
    echo -e "${YELLOW}⚠ WARNING${NC}: Security vulnerabilities may exist"
    check_result 1 "Security audit failed"
fi
echo ""

# Summary
echo "=========================================="
echo "Pre-Deployment Check Summary"
echo "=========================================="
echo -e "${GREEN}Passed: $CHECKS_PASSED${NC}"
echo -e "${RED}Failed: $CHECKS_FAILED${NC}"
echo ""

if [ $CHECKS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All pre-deployment checks passed!${NC}"
    echo "System is ready for deployment."
    exit 0
else
    echo -e "${RED}✗ Some pre-deployment checks failed.${NC}"
    echo "Please fix the issues before deploying."
    exit 1
fi
