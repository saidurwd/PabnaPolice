#!/usr/bin/env bash
# Run on the server, inside the application folder, AFTER the new code has been put in place.
#
#   ./scripts/deploy.sh --check    -> read-only: shows pending migrations and the health report, changes nothing
#   ./scripts/deploy.sh            -> backs up the database, migrates, rebuilds caches, brings the site back up
#
# Safe to re-run. If a step fails the site is brought back up and the backup file is named in the output.
set -euo pipefail
cd "$(dirname "$0")/.."

PHP="${PHP_BIN:-php}"
say() { printf '\n==> %s\n' "$*"; }

"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' || { echo "PHP 8.3 or newer is required (found $("$PHP" -v | head -1))."; exit 1; }
[ -f .env ] || { echo ".env is missing. Copy .env.example to .env and fill it in."; exit 1; }
[ -f vendor/autoload.php ] || { echo "vendor/ is missing: upload a release built with scripts/build-release.sh."; exit 1; }
[ -f public/build/manifest.json ] || { echo "public/build is missing: upload a release built with scripts/build-release.sh."; exit 1; }

if [ "${1:-}" = "--check" ]; then
  say "Migration status"; "$PHP" artisan migrate:status || true
  say "SQL that migrate would run (nothing is executed)"; "$PHP" artisan migrate --pretend || true
  say "Health"; "$PHP" artisan app:health || true
  exit 0
fi

DOWN=0
cleanup() { if [ "$DOWN" = 1 ]; then "$PHP" artisan up >/dev/null 2>&1 || true; echo "Site brought back up."; fi; }
trap cleanup EXIT

say "Backing up the database"
"$PHP" artisan backup:run --only-db --disable-notifications
BACKUP="$(ls -t storage/app/private/*/*.zip 2>/dev/null | head -1 || true)"
[ -n "$BACKUP" ] && echo "Backup: $BACKUP" || { echo "No backup file was created; aborting."; exit 1; }

say "Maintenance mode on"
"$PHP" artisan down --retry=60 && DOWN=1

say "Running migrations"
"$PHP" artisan migrate --force

say "Rebuilding caches"
"$PHP" artisan optimize:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" artisan event:cache

say "Maintenance mode off"
"$PHP" artisan up && DOWN=0

say "Health"
"$PHP" artisan app:health || { echo "Health check reported a failure: see above. Rollback = restore $BACKUP and the previous release."; exit 1; }
echo
echo "Deployed. Database backup before this deploy: $BACKUP"
