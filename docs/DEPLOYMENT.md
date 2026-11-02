# Deployment guide

Works on a plain cPanel/shared host (no Node or Composer needed on the server) and on a VPS.

## 1. One-time server setup
1. **PHP 8.3 or newer** with these extensions: `mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, fileinfo, gd` (cPanel: *Select PHP Version*).
   The old Yii site ran on PHP 7.3 (`ea-php73`): switch the domain to PHP 8.3+, otherwise the site will not start.
2. **Document root** must be the project's `public/` folder (cPanel: *Domains > Document Root*). Everything else
   (`.env`, `app/`, `storage/`, `vendor/`) stays outside the web root. Never serve the project root.
3. Create `.env` from `.env.example` and fill in `APP_URL`, `DB_*`, `MAIL_*`, `BACKUP_NOTIFY_EMAIL`, `HEALTH_TOKEN`
   (use `APP_ENV=production`, `APP_DEBUG=false`), then `php artisan key:generate`.
   Behind a reverse proxy / CDN? Also set `TRUSTED_PROXIES`.
4. Make these writable by the web user: `storage/`, `bootstrap/cache/`, `public/uploads/`.
5. **Cron** (every minute) so the scheduler can run backups, log pruning and health checks:
   `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`
6. Point an uptime monitor (UptimeRobot, Better Stack, ...) at `https://your-site/health`. It returns HTTP 200 `{"status":"ok"}`
   or HTTP 503 when the database, migrations, folders or admin assets are broken.
   `https://your-site/health?token=<HEALTH_TOKEN>` adds the detail of every check.

## 2. Every release
On your own machine (PHP 8.3+, Composer, Node 20+):
```
./scripts/build-release.sh          # creates release/pabna-police-<time>.zip (no .env, no uploads, no dev packages)
```
On the server:
1. Upload and unzip the release over the application folder. **Do not overwrite** `.env` or `public/uploads/`.
2. Preview, changing nothing: `./scripts/deploy.sh --check` (pending migrations, the SQL they would run, health report).
3. Deploy: `./scripts/deploy.sh`. It backs up the database, switches maintenance mode on, runs the migrations, rebuilds the
   caches, switches maintenance mode off and prints the health report. If a step fails the site is brought back up and the
   backup file is named in the output.

Use a different PHP binary with `PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php ./scripts/deploy.sh`.

### First production release (cutting over from Yii)
1. Back up the database and `uploads/`, then switch the Yii site off (or stop sending it traffic).
2. Upload the release, create `.env`, set `YII_RETIRED=true`.
3. `./scripts/deploy.sh --check` (shows what will run; the Phase 4 migrations take a few minutes on the real data).
4. `./scripts/deploy.sh`. It backs up the database first and aborts on any row-count mismatch.
   If anything fails, restore the backup and the Yii site is exactly as it was. After a successful run the old Yii app can no longer
   use this database (tables and columns were renamed): that is the point of no return, so keep the backup.

## 3. Rolling back
* Code: unzip the previous release zip over the folder and run `php artisan optimize:clear`.
* Database: migrations only ever add tables/columns (see `docs/DATABASE_CHANGE_RULES.md`), so older code keeps working with a
  newer database. If data must be restored, import the backup zip created by the deploy script (`storage/app/private/<site name>/`)
  or your own `mysqldump`.

## 4. Logs and monitoring
* Application errors: `storage/logs/laravel-YYYY-MM-DD.log` (daily files, the last 14 are kept; set `LOG_DAILY_DAYS` to change).
* The hourly self-check writes failures and warnings to the log as `Health check <name>: ...`.
* Nightly backups of the database and `public/uploads/` go to `storage/app/private/<site name>/` and old ones are pruned.
  **Copy them off the server** (a second disk, S3 or another host): a backup on the same machine does not survive losing it.
  Set `BACKUP_NOTIFY_EMAIL` (and working `MAIL_*`) to be told when a backup fails.
* Admin visits and logins older than 180 days are deleted weekly.

## 5. Not covered yet
* **Content-Security-Policy**: the public theme uses inline scripts and third-party hosts (Google Fonts, Maps). A policy should be
  introduced in report-only mode first.
* **Error tracking service** (Sentry, Flare, ...): optional; add its package and DSN when you want alerts beyond the log file.
