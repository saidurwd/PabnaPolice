# SP Pabna Police website (Laravel)

Replaces the old Yii 1 application (a copy of its code is kept outside the repo in `PabnaPolice-legacy-backup/`).

* **Website**: same design (Bootstrap 3 theme assets copied unchanged to `public/assets`), same `.html` URLs.
* **Admin panel** at `/admin` (custom Blade + Bootstrap 5, no CDN): content, categories, banners/gallery, documents,
  menus, users, groups and permissions, places, **site settings**, **site links** (every list that was hardcoded
  in the old theme), feedback inbox, audit trail and visitor log.

## Data safety: how the live database is handled

The data is never copied or exported: the app works on the existing database in place. Migrations first add what the new site needs
(settings, site links, feedback, password reset, two-factor), then the **Phase 4** migrations (below) bring the old Yii tables up to
Laravel conventions. Those need `YII_RETIRED=true`: they make the old Yii app unusable.

| Migration | What it does on the live DB |
|---|---|
| `create_legacy_tables` | Creates a table only if it is missing (names are the OLD Yii names; Phase 4 renames them). On the live DB it does nothing. `down()` is empty on purpose. |
| `update_user_table_for_laravel` | Adds `user.remember_token`; makes `user.photo` nullable. No data changes. |
| `create_setting_table` | New table; seeded with the values that were hardcoded in the old config/theme. |
| `create_site_link_table` | New table; seeded with the old hardcoded link lists (only when the table is first created). |
| `create_feedback_table` | New table for contact-form messages. |
| `create_password_reset_token_table` | New table for password-reset tokens. |
| `add_two_factor_columns_to_user_table` | Adds three nullable two-factor columns to `user`. No data changes. |
| `register_new_admin_modules` | Adds menu/ACL rows for the new admin modules (idempotent, never edits existing rows). |

The migrations up to this point are all additive. The **Phase 4** migrations (`2026_11_10_*`, gated by `YII_RETIRED`) then:

| Step | What changes | Data impact |
|---|---|---|
| 1 rename tables | `os_content` -> `os_contents`, `os_user` -> `os_users`, `os_acl` -> `os_acl_rules`, ... (plural, the `os_` prefix stays) | none |
| 2 character set | `utf8mb3` -> `utf8mb4` | none (lossless) |
| 3 columns | `category` -> `category_id`, `parent` -> `parent_id` (0 -> NULL), `created_on` -> `published_at` plus `created_at`/`updated_at`, user `register_date`/`lastvisit` -> `created_at`/`last_login_at` (zero dates -> NULL), ENUMs -> VARCHAR | values preserved; only "0" parents and zero dates become NULL |
| 4 keys and indexes | 23 foreign keys, listing indexes. Stops without changes if any row points at a missing parent | none |
| 5 soft deletes | `deleted_at` on contents, banners, documents; deleting in the admin now moves to a restorable trash | none |
| 6 cleanup | drops the unused Yii session table | session blobs only |

Each step compares row counts before/after and aborts on any difference. Tested on a copy of the real data: content, categories, banners,
documents, users, menus, places and permissions came out identical under the new names, with all 23 tables' row counts equal.
The migration test on a copy of the live data showed `content`, `category`, `banner`, `document` and `user` byte-identical
before/after; the only differences were the new tables, +3 `menu` rows and +36 `acl` rows.

## Deploying

See **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)**: server setup (PHP 8.3, document root, cron), `scripts/build-release.sh`
(builds the zip on your machine) and `scripts/deploy.sh` (backup, migrate, rebuild caches, health check; `--check` to preview).
`php artisan app:health` and `GET /health` report whether the site is healthy.

## Go-live runbook

1. **Back up** the database (`mysqldump --single-transaction DBNAME > backup.sql`) and the `uploads/` folder.
2. **Rehearse on a copy first**: restore the dump into a scratch database, point `.env` at it, and run the steps below.
3. Deploy the code, then `cp .env.example .env`, fill in `APP_URL`, `DB_*`, and run `php artisan key:generate`.
4. Install: `composer install --no-dev --optimize-autoloader`, then build the admin assets once (`npm ci && npm run build`)
   on any machine with Node 20+ and upload `public/build/` if the server has no Node. The public site needs no build step.
5. See exactly what will run, then run it:
   ```
   php artisan migrate:status
   php artisan migrate --pretend      # prints the SQL, changes nothing
   php artisan migrate --force
   ```
6. **Uploads** stay where they are. Serve them at `/uploads/...` by pointing `public/uploads` at the existing folder
   (`ln -s /path/to/existing/uploads public/uploads`) or by moving the Laravel `public/` onto the same document root.
   Make `uploads/` writable by the web user. Uploaded files are never deleted by the admin.
7. Web server document root must be this project's `public/` folder. Keep `.env`, `storage/` and `vendor/` outside the web root.
8. `chmod -R ug+rw storage bootstrap/cache`, then:
   ```
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
9. Smoke test: home, a content page, a document download, the gallery, the contact form, and an admin login.

**Rollback:** the old Yii app keeps working against the same database (only additive changes), so rolling back is
just switching the web server back. Passwords stay SHA1 (`LEGACY_PASSWORD_UPGRADE=false`) so both admins work in parallel;
turn the flag on only after the Yii admin is retired.

## Logging in

Existing admin users and passwords keep working (legacy SHA1 is verified at login). Access is controlled by the existing
`acl` table (group x module x action), editable at **Access Control > User Group > Permissions**.
Super Users (group 1) always have full access. Anything without an ACL row is denied.

## Accounts and passwords

* **Forgot password** (`/admin/forgot-password`) e-mails a one-hour reset link. This needs working `MAIL_*` settings in `.env`
  (with `MAIL_MAILER=log` the link is written to `storage/logs/laravel.log` instead, handy for local testing).
  The form gives the same answer for unknown addresses, so it can't be used to discover accounts.
* **My account** (`/admin/account`, click your name at the top): edit your name and e-mail, change your password (the current
  one is required; at least 8 characters with letters and numbers) and turn on **two-factor authentication** with any authenticator app.
  With two-factor on, sign-in also asks for the 6-digit code; 8 one-time recovery codes are shown once when you enable it.
* Sign-in attempts are rate limited (5 wrong passwords per user and address, 5 wrong codes), and each sign-in is written to the audit trail.
* **Legacy passwords:** run `php artisan users:password-report` to see which users still have an old SHA1 hash. They keep working;
  after the old Yii admin is retired, set `LEGACY_PASSWORD_UPGRADE=true` and each user is moved to bcrypt the next time they sign in
  or reset their password (SHA1 cannot be converted without the plain password).

## Making the website editable

* **Admin > Configuration > Site Settings**: site name, social links, control-room box, section headings, the content
  and category IDs used on the homepage, the main-menu categories, page sizes, map embed.
* **Admin > Configuration > Site Links**: homepage buttons, side panels, widgets, important-link tiles, footer links, app badges.

## Quality tools (run in CI on every push, see `.github/workflows/ci.yml`)

```
./vendor/bin/pint            # code style (use --test to only check)
./vendor/bin/phpstan analyse # Larastan static analysis, level 5, no baseline
php artisan test             # includes HTML snapshots of the public pages (tests/snapshots)
```

If you change public markup on purpose, refresh the snapshots with `UPDATE_SNAPSHOTS=1 php artisan test --filter=SnapshotTest`
and review the diff. **Any change that touches the database must follow [docs/DATABASE_CHANGE_RULES.md](docs/DATABASE_CHANGE_RULES.md).**

Nightly (via `php artisan schedule:run` in cron): database + uploads backup to `storage/app/private/`, old backups pruned,
and visitor/audit rows older than 180 days deleted. Set `BACKUP_NOTIFY_EMAIL` and the mail settings to get failure notices.

## Local development

```
composer install
cp .env.example .env && php artisan key:generate     # point DB_* at a COPY of the data
php artisan migrate
php artisan serve
mysql -e 'create database pabnapolice_laravel_test'   # once; tests wipe this database, never point it at real data
php artisan test
```

`public/uploads` holds the existing uploaded files (git-ignored). Requires PHP 8.3+ (the old cPanel `ea-php73` handler from the Yii `.htaccess` must not be carried over).
