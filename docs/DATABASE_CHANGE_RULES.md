# Rules for every change that touches the live database

The database holds the real site. Follow these for **every** migration, no exceptions.

1. **Additive first (expand), destructive last (contract).** Add the new column/table, copy data, switch the code,
   and only in a *later* release remove the old one. Never rename or drop in the same release as the code change.
2. **A migration never loses data.** `down()` of anything that could drop rows or columns is empty or throws.
   Guard creates with `Schema::hasTable/hasColumn` so it is safe to re-run on the live database.
3. **Back up, then rehearse.** `mysqldump --single-transaction` first, then run the whole sequence on a restored copy.
4. **Look before you run:** `php artisan migrate:status` then `php artisan migrate --pretend`.
5. **Prove nothing was lost:** record `CHECKSUM TABLE` and row counts before and after for every table that is not
   supposed to change; only the intended tables may differ.
6. **Mind legacy data quirks:** `0000-00-00` dates (strict mode rejects `ALTER`; see `update_user_table_for_laravel`),
   `utf8mb3` columns, ENUM strings and ID columns without foreign keys.
7. **Keep both apps working while Yii is still deployed.** Do not rename tables/columns until the Yii app is retired.
8. **Breaking changes are gated.** Anything that makes the old Yii app unusable extends `App\Support\BreakingMigration`,
   which refuses to run unless `YII_RETIRED=true`, verifies row counts before/after, and relaxes the SQL mode only where needed.
   The Phase 4 migrations (`2026_11_10_*`) are of this kind. They check for orphaned references first and stop without
   changing anything if they find any.
9. Add or update a test for the behaviour, and run `php artisan test`, `./vendor/bin/pint --test` and `./vendor/bin/phpstan analyse`.
