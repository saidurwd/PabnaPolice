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
say() { STEP="$*"; printf '\n==> %s\n' "$*"; }

"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' || { echo "PHP 8.3 or newer is required (found $("$PHP" -v | head -1))."; exit 1; }
[ -f .env ] || { echo ".env is missing. Copy .env.example to .env and fill it in."; exit 1; }
[ -f vendor/autoload.php ] || { echo "vendor/ is missing: upload a release built with scripts/build-release.sh."; exit 1; }
[ -f public/build/manifest.json ] || { echo "public/build is missing: upload a release built with scripts/build-release.sh."; exit 1; }

if [ "${1:-}" = "--check" ]; then
  say "Migration status (\"Migration table not found\" is normal on the very first deployment)"; "$PHP" artisan migrate:status || true
  say "SQL that migrate would run (nothing is executed)"; "$PHP" artisan migrate --pretend --force || true
  say "Health"; "$PHP" artisan app:health || true
  exit 0
fi

DOWN=0
MIGRATING=0
STEP="starting"
BACKUP=""
cleanup() {
  code=$?
  if [ "$DOWN" = 1 ] && { [ "$code" = 0 ] || [ "$MIGRATING" = 0 ]; }; then
    "$PHP" artisan up >/dev/null 2>&1 || true; echo "Site brought back up."
  elif [ "$DOWN" = 1 ]; then
    echo "Site LEFT IN MAINTENANCE MODE on purpose: the database may be half-migrated. Run  $PHP artisan up  once it is fixed or restored."
  fi
  if [ "$code" != 0 ]; then
    echo
    echo "DEPLOY FAILED during: $STEP"
    if [ -n "$BACKUP" ]; then
      echo "The database may be PARTLY migrated. Do not keep running the new code against it."
      echo "Roll back: restore the database from  $BACKUP"
      echo "  (unzip it and import the .sql file inside db-dumps/), then put the previous release back."
      echo "Or fix the reported problem and run ./scripts/deploy.sh again: the migrations skip what is already done."
    fi
  fi
}
trap cleanup EXIT

say "Backing up the database"
"$PHP" artisan backup:run --only-db --disable-notifications
BACKUP="$(ls -t storage/app/private/*/*.zip 2>/dev/null | head -1 || true)"
[ -n "$BACKUP" ] && echo "Backup: $BACKUP" || { echo "No backup file was created; aborting."; exit 1; }

say "Maintenance mode on"
"$PHP" artisan down --retry=60 && DOWN=1

say "Running migrations"
MIGRATING=1
"$PHP" artisan migrate --force
MIGRATING=0

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
