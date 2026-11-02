# Rules for every change that touches the live database

The database holds the real site. Follow these for **every** migration, no exceptions.

1. **Additive first (expand), destructive last (contract).** Add the new column/table, copy data, switch the code,
   and only in a *later* release remove the old one. Never rename or drop in the same release as the code change.
2. **A migration never loses data.** `down()` of anything that could drop rows or columns is empty or throws.
   Guard creates with `Schema::hasTable/hasColumn` so it is safe to re-run on the live database.
3. **Back up, then rehearse.** `mysqldump --single-transaction` first, then run the whole sequence on a restored copy.
4. **Look before you run:** `php artisan migrate:status` then `php artisan migrate --pretend`.
5. **Prove nothing was lost:** record row counts for every table, and compare row-level fingerprints before and after.
   Hash **each row** (`MD5(CONCAT_WS('|', col1, col2, ...))`) and then hash the list ordered by id. Do **not** hash one big
   `GROUP_CONCAT` of the raw rows: it silently truncates at `group_concat_max_len` (1 KB by default), so it only checks the first
   few rows and would call two different tables "identical". Hash dates as `UNIX_TIMESTAMP(...)` so renamed/retyped columns compare.
   Only the intended tables may differ, and for those every original row must still be present unchanged.
6. **Mind legacy data quirks:** `0000-00-00` dates (strict mode rejects `ALTER`; see `update_user_table_for_laravel`),
   `utf8mb3` columns, ENUM strings and ID columns without foreign keys.
7. **Keep both apps working while Yii is still deployed.** Do not rename tables/columns until the Yii app is retired.
8. **Breaking changes are gated.** Anything that makes the old Yii app unusable extends `App\Support\BreakingMigration`,
   which refuses to run unless `YII_RETIRED=true`, verifies row counts before/after, and relaxes the SQL mode only where needed.
   The Phase 4 migrations (`2026_11_10_*`) are of this kind. They check for orphaned references first and stop without
   changing anything if they find any.
9. **A check that uses the same assumption as the change proves nothing.** The user-groups-to-roles migration once passed its own
   equivalence test while silently dropping five permissions, because both sides used the same case-sensitive module lookup
   ("Country" vs "country"). When converting data, (a) fail loudly on any input row that does not map to something and is not on an
   explicit "dropped on purpose" list, and (b) read a before/after listing of the real data (who can do what) as a human.
10. Anything that drops tables first dumps them to `storage/app/private/removed-tables/` and aborts if the dump is incomplete.
11. Add or update a test for the behaviour, and run `php artisan test`, `./vendor/bin/pint --test` and `./vendor/bin/phpstan analyse`.
