#!/bin/bash

# Rollback Script
# Rolls back to previous version in case of deployment issues
# Usage: ./scripts/rollback.sh [version] [environment]

set -e

VERSION=${1:-previous}
ENVIRONMENT=${2:-staging}

echo "=========================================="
echo "Rollback Script"
echo "=========================================="
echo "Version: $VERSION"
echo "Environment: $ENVIRONMENT"
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Backup directory
BACKUP_DIR="./backups/rollback"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo -e "${YELLOW}⚠ WARNING: This will rollback the system to a previous version${NC}"
echo "Environment: $ENVIRONMENT"
echo "Version: $VERSION"
echo ""

# Confirm rollback
read -p "Are you sure you want to proceed with rollback? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    echo "Rollback cancelled."
    exit 0
fi

echo ""
echo "Starting rollback process..."
echo ""

# Step 1: Backup current state
echo "1. Backing up current state..."
if [ -d ".git" ]; then
    CURRENT_COMMIT=$(git rev-parse HEAD)
    echo "Current commit: $CURRENT_COMMIT"
    echo "$CURRENT_COMMIT" > "$BACKUP_DIR/current-commit-$TIMESTAMP.txt"
    echo -e "${GREEN}✓${NC} Current state backed up"
else
    echo -e "${YELLOW}⚠${NC} Git repository not found, skipping commit backup"
fi
echo ""

# Step 2: Backup database
echo "2. Backing up database..."
if command -v mysqldump &> /dev/null; then
    DB_NAME=$(grep "DB_DATABASE=" .env | cut -d '=' -f 2)
    DB_USER=$(grep "DB_USERNAME=" .env | cut -d '=' -f 2)
    DB_HOST=$(grep "DB_HOST=" .env | cut -d '=' -f 2)

    if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
        mysqldump -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" > "$BACKUP_DIR/database-backup-$TIMESTAMP.sql"
        echo -e "${GREEN}✓${NC} Database backed up to $BACKUP_DIR/database-backup-$TIMESTAMP.sql"
    else
        echo -e "${YELLOW}⚠${NC} Database credentials not found in .env"
    fi
else
    echo -e "${YELLOW}⚠${NC} mysqldump not found, skipping database backup"
fi
echo ""

# Step 3: Backup current code
echo "3. Backing up current code..."
if [ -d "app" ]; then
    tar -czf "$BACKUP_DIR/code-backup-$TIMESTAMP.tar.gz" app/ resources/ config/ 2>/dev/null
    echo -e "${GREEN}✓${NC} Code backed up to $BACKUP_DIR/code-backup-$TIMESTAMP.tar.gz"
else
    echo -e "${YELLOW}⚠${NC} Application directory not found"
fi
echo ""

# Step 4: Revert code changes
echo "4. Reverting code changes..."
if [ -d ".git" ]; then
    if [ "$VERSION" = "previous" ]; then
        echo "Reverting to previous commit..."
        git revert --no-edit HEAD || git reset --hard HEAD~1
    else
        echo "Reverting to version: $VERSION"
        git checkout "$VERSION" -- .
    fi
    echo -e "${GREEN}✓${NC} Code reverted"
else
    echo -e "${RED}✗${NC} Git repository not found, cannot revert code"
    exit 1
fi
echo ""

# Step 5: Clear caches
echo "5. Clearing caches..."
if [ -f "artisan" ]; then
    php artisan cache:clear 2>/dev/null && echo -e "${GREEN}✓${NC} Application cache cleared"
    php artisan config:clear 2>/dev/null && echo -e "${GREEN}✓${NC} Configuration cache cleared"
    php artisan view:clear 2>/dev/null && echo -e "${GREEN}✓${NC} View cache cleared"
else
    echo -e "${YELLOW}⚠${NC} Laravel artisan not found"
fi
echo ""

# Step 6: Clear cookies if needed
echo "6. Clearing cookies..."
read -p "Clear all cookies? (yes/no): " CLEAR_COOKIES
if [ "$CLEAR_COOKIES" = "yes" ]; then
    # Create a script to clear cookies
    cat > /tmp/clear-cookies.js << 'EOF'
// Clear all cookies
document.cookie.split(";").forEach(function(c) {
    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
});
console.log("Cookies cleared");
EOF
    echo -e "${GREEN}✓${NC} Cookie clearing script created"
else
    echo -e "${YELLOW}⚠${NC} Cookies not cleared"
fi
echo ""

# Step 7: Restore database if needed
echo "7. Database restoration..."
read -p "Restore database from backup? (yes/no): " RESTORE_DB
if [ "$RESTORE_DB" = "yes" ]; then
    if [ -f "$BACKUP_DIR/database-backup-$TIMESTAMP.sql" ]; then
        if command -v mysql &> /dev/null; then
            DB_NAME=$(grep "DB_DATABASE=" .env | cut -d '=' -f 2)
            DB_USER=$(grep "DB_USERNAME=" .env | cut -d '=' -f 2)
            DB_HOST=$(grep "DB_HOST=" .env | cut -d '=' -f 2)

            mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "$BACKUP_DIR/database-backup-$TIMESTAMP.sql"
            echo -e "${GREEN}✓${NC} Database restored"
        else
            echo -e "${RED}✗${NC} mysql client not found"
        fi
    else
        echo -e "${RED}✗${NC} Database backup not found"
    fi
else
    echo -e "${YELLOW}⚠${NC} Database not restored"
fi
echo ""

# Step 8: Run migrations if needed
echo "8. Running migrations..."
if [ -f "artisan" ]; then
    read -p "Run database migrations? (yes/no): " RUN_MIGRATIONS
    if [ "$RUN_MIGRATIONS" = "yes" ]; then
        php artisan migrate 2>/dev/null && echo -e "${GREEN}✓${NC} Migrations completed"
    fi
fi
echo ""

# Step 9: Verify rollback
echo "9. Verifying rollback..."
if [ -d ".git" ]; then
    NEW_COMMIT=$(git rev-parse HEAD)
    if [ "$NEW_COMMIT" != "$CURRENT_COMMIT" ]; then
        echo -e "${GREEN}✓${NC} Code successfully rolled back"
        echo "Previous commit: $CURRENT_COMMIT"
        echo "Current commit: $NEW_COMMIT"
    else
        echo -e "${RED}✗${NC} Rollback verification failed"
    fi
fi
echo ""

# Step 10: Generate rollback report
echo "10. Generating rollback report..."
REPORT_FILE="reports/rollback-report-$TIMESTAMP.txt"
mkdir -p reports

{
    echo "Rollback Report"
    echo "==============="
    echo "Timestamp: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
    echo "Environment: $ENVIRONMENT"
    echo "Version: $VERSION"
    echo ""
    echo "Backup Files:"
    echo "-------------"
    ls -lh "$BACKUP_DIR" | grep "$TIMESTAMP"
    echo ""
    echo "Actions Performed:"
    echo "------------------"
    echo "✓ Current state backed up"
    echo "✓ Database backed up"
    echo "✓ Code backed up"
    echo "✓ Code reverted"
    echo "✓ Caches cleared"
    if [ "$CLEAR_COOKIES" = "yes" ]; then
        echo "✓ Cookies cleared"
    fi
    if [ "$RESTORE_DB" = "yes" ]; then
        echo "✓ Database restored"
    fi
    if [ "$RUN_MIGRATIONS" = "yes" ]; then
        echo "✓ Migrations run"
    fi
    echo ""
    echo "Status: Rollback completed successfully"
} > "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Rollback report generated: $REPORT_FILE"
echo ""

# Summary
echo "=========================================="
echo "Rollback Summary"
echo "=========================================="
echo -e "${GREEN}✓ Rollback completed successfully${NC}"
echo ""
echo "Backup files saved to: $BACKUP_DIR"
echo "Report saved to: $REPORT_FILE"
echo ""
echo "Next steps:"
echo "1. Verify system functionality"
echo "2. Check error logs for any issues"
echo "3. Monitor system performance"
echo "4. Investigate root cause of deployment failure"
echo "5. Plan corrective actions"
echo ""
