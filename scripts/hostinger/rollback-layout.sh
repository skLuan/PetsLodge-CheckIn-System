#!/usr/bin/env bash
# Undo migrate-layout.sh: restore public_html from the newest backup.
#   bash rollback-layout.sh [path/to/public_html-YYYYmmdd-HHMMSS.tar.gz]
# The app files moved up to ~/domains/petlodge.fun are left in place;
# delete them by hand once the old site works again.
# WARNING: anything written to storage/ after the migration (new signatures, PDFs)
# lives in $DOMAIN_DIR/storage, not in the restored backup. Copy it back before deleting.
set -euo pipefail
DOMAIN_DIR="${DOMAIN_DIR:-$HOME/domains/petlodge.fun}"
PHP_BIN="${PHP_BIN:-php}"
BACKUP="${1:-$(ls -1t "$HOME"/backups/public_html-*.tar.gz | head -1)}"
[ -f "$BACKUP" ] || { echo "No backup found"; exit 1; }
echo "Restoring $BACKUP"
cd "$DOMAIN_DIR"
if [ -L public_html ]; then rm public_html; elif [ -e public_html ]; then mv public_html "public_html.broken-$(date +%s)"; fi
tar -xzf "$BACKUP" -C "$DOMAIN_DIR"
cd public_html
"$PHP_BIN" artisan optimize:clear || true
"$PHP_BIN" artisan up || true
echo "public_html restored. Set HOSTINGER_DEPLOY_PATH back to $DOMAIN_DIR/public_html if you need to deploy before migrating again."
echo "Copy any post-migration files from $DOMAIN_DIR/storage/app/{signatures,pdfs} into public_html/storage/app/ first."
