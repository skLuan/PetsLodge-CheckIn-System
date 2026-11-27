#!/usr/bin/env bash
# One-time migration on Hostinger: move the Laravel app out of public_html.
# Ported from skLuan/PersonalLaravelWepPage (luane.online) — see ai/plans Plan 08, Step 0.
#
#   Before:  ~/domains/petlodge.fun/public_html/{app,vendor,.env,public,...}
#   After:   ~/domains/petlodge.fun/{app,vendor,.env,public,...}
#            ~/domains/petlodge.fun/public_html -> public   (symlink)
#
# Only Laravel's public/ folder is served. Run it over SSH on the server:
#   bash migrate-layout.sh            # dry run: only checks and prints the plan
#   bash migrate-layout.sh --apply    # does it (makes a backup first)
# Undo with rollback-layout.sh.
#
# PetsLodge does not use the public disk, so no public/storage link is created
# (Plan 08 "Verified facts"). storage/app/signatures/ is legal evidence: it moves
# with the rest of storage/ and is included in the backup.
set -euo pipefail

DOMAIN_DIR="${DOMAIN_DIR:-$HOME/domains/petlodge.fun}"
SITE_URL="${SITE_URL:-https://petlodge.fun}"
PHP_BIN="${PHP_BIN:-php}"
OLD="$DOMAIN_DIR/public_html"
BACKUP_DIR="$HOME/backups"
STAMP="$(date +%Y%m%d-%H%M%S)"
APPLY=0; [ "${1:-}" = "--apply" ] && APPLY=1

say()  { printf '\n==> %s\n' "$*"; }
fail() { printf '\nERROR: %s\n' "$*" >&2; exit 1; }

cd "$HOME"   # public_html is removed later; do not run from inside it

say "Checks"
[ -d "$DOMAIN_DIR" ] || fail "$DOMAIN_DIR does not exist (set DOMAIN_DIR=...)"
[ -L "$OLD" ] && fail "public_html is already a symlink -> $(readlink "$OLD"). Nothing to do."
[ -f "$OLD/artisan" ] || fail "$OLD/artisan not found: the app is not in public_html"
[ -f "$OLD/public/index.php" ] || fail "$OLD/public/index.php not found"
"$PHP_BIN" -v | head -1

# Every top-level entry in public_html moves up one level; nothing there may clash.
conflicts=0
shopt -s dotglob nullglob
for item in "$OLD"/*; do
  name="$(basename "$item")"
  [ "$name" = ".htaccess" ] && name=".htaccess.public_html-old"
  if [ -e "$DOMAIN_DIR/$name" ] || [ -L "$DOMAIN_DIR/$name" ]; then
    echo "  clash: $DOMAIN_DIR/$name already exists"; conflicts=1
  fi
done
[ $conflicts -eq 0 ] || fail "Rename or remove the clashing entries above, then run again."
echo "  no name clashes in $DOMAIN_DIR"

if [ -d "$OLD/storage/app/signatures" ]; then
  echo "  signatures on disk: $(find "$OLD/storage/app/signatures" -type f | wc -l) files"
fi

echo
echo "Plan:"
echo "  1. backup   $OLD -> $BACKUP_DIR/public_html-$STAMP.tar.gz"
echo "  2. artisan down"
echo "  3. move every entry of public_html/ up to $DOMAIN_DIR/"
echo "     (root .htaccess that redirected to public/ is kept as .htaccess.public_html-old)"
echo "  4. public_html -> public (relative symlink)"
echo "  5. clear and rebuild caches (config:cache + view:cache, never route:cache)"
echo "  6. artisan up + check $SITE_URL/"

if [ $APPLY -eq 0 ]; then
  echo; echo "Dry run only. Re-run with --apply to do it."; exit 0
fi

say "1. Backup"
mkdir -p "$BACKUP_DIR"
tar --exclude='public_html/node_modules' -czf "$BACKUP_DIR/public_html-$STAMP.tar.gz" -C "$DOMAIN_DIR" public_html
ls -lh "$BACKUP_DIR/public_html-$STAMP.tar.gz"

say "2. Maintenance mode"
(cd "$OLD" && "$PHP_BIN" artisan down --retry=15) || true

say "3. Move files up"
[ -f "$OLD/.htaccess" ] && mv "$OLD/.htaccess" "$DOMAIN_DIR/.htaccess.public_html-old"
for item in "$OLD"/*; do
  mv "$item" "$DOMAIN_DIR/"
done
rmdir "$OLD"

say "4. public_html -> public"
cd "$DOMAIN_DIR"
ln -s public public_html
ls -la "$DOMAIN_DIR" | grep public

say "5. Caches"
# Cached config holds the old absolute paths into public_html.
rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan view:cache
mkdir -p bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache || true

say "6. Back online"
"$PHP_BIN" artisan up
for p in / /check-in /.env /artisan /composer.json /vendor/autoload.php /storage/logs/laravel.log /public/index.php; do
  printf '  %-28s %s\n' "$p" "$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL$p")"
done
echo
echo "Done. Expected: / and /check-in 200 (or 302); the rest 403/404."
echo "GitHub secret HOSTINGER_DEPLOY_PATH must now be: $DOMAIN_DIR"
echo "Repoint the queue cron to: cd $DOMAIN_DIR && ..."
echo "Backup: $BACKUP_DIR/public_html-$STAMP.tar.gz  (undo: bash rollback-layout.sh)"
