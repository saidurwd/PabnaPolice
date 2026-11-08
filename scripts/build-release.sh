#!/usr/bin/env bash
# Builds a ready-to-upload release zip on your own machine (needs PHP 8.3+, Composer, Node 20+, zip).
# The server then needs neither Composer nor Node. It never includes .env, uploads or logs.
#
#   ./scripts/build-release.sh            -> release/pabna-police-<timestamp>.zip
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAMP="$(date +%Y%m%d-%H%M%S)"
WORK="$(mktemp -d)"
OUT="$ROOT/release"
trap 'rm -rf "$WORK"' EXIT

echo "==> Copying source (without secrets, uploads, dev folders)"
rsync -a --delete \
  --exclude '.git' --exclude '.github' --exclude '.vscode' --exclude '.env' --exclude '.env.*' --exclude '!.env.example' \
  --exclude 'node_modules' --exclude 'vendor' --exclude 'tests' --exclude 'release' \
  --exclude 'public/uploads' --exclude 'public/build' --exclude 'public/hot' \
  --exclude 'storage/logs/*' --exclude 'storage/framework/cache/*' --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' --exclude 'storage/app/private/*' --exclude 'bootstrap/cache/*.php' \
  --exclude '.phpunit.cache' --exclude '.phpunit.result.cache' --exclude 'CLAUDE.md' --exclude 'AGENTS.md' \
  "$ROOT/" "$WORK/app/"
cp "$ROOT/.env.example" "$WORK/app/.env.example"

cd "$WORK/app"
echo "==> Installing PHP dependencies (production only)"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

echo "==> Building admin assets"
npm ci --no-audit --no-fund --silent
npm run build --silent
rm -rf node_modules

mkdir -p storage/logs storage/framework/{cache/data,sessions,views} storage/app/private bootstrap/cache
touch storage/logs/.gitkeep

mkdir -p "$OUT"
ZIP="$OUT/pabna-police-$STAMP.zip"
zip -rq "$ZIP" . -x '*.DS_Store'
echo "==> Done: $ZIP ($(du -h "$ZIP" | cut -f1))"
echo "    Upload and unzip it on the server (keep the existing .env and public/uploads), then run scripts/deploy.sh"
