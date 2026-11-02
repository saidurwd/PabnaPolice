<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Base for the Phase 4 "schema normalisation" migrations. They rename tables/columns, convert character sets and
 * add foreign keys, which makes the old Yii application unable to use the database.
 *
 * Safety rails:
 *  - refuses to run until YII_RETIRED=true is set in .env (config('legacy.yii_retired'));
 *  - records every table's row count first and aborts if any count differs afterwards;
 *  - relaxes the SQL mode only inside the statements that need it (legacy zero dates break strict ALTERs).
 */
abstract class BreakingMigration extends Migration
{
    protected function ensureYiiRetired(): void
    {
        if (! config('legacy.yii_retired')) {
            throw new RuntimeException(
                "This migration renames/converts the live tables and makes the old Yii app unusable.\n".
                'Switch the Yii app off, take a backup, then set YII_RETIRED=true in .env and run it again.'
            );
        }
    }

    /** Fully prefixed table name. */
    protected function t(string $table): string
    {
        return DB::getTablePrefix().$table;
    }

    protected function sql(string $statement): void
    {
        DB::statement($statement);
    }

    /** Runs $callback with a permissive SQL mode (zero dates, etc.) and always restores the previous mode. */
    protected function withRelaxedSqlMode(Closure $callback): void
    {
        $mode = DB::selectOne('select @@SESSION.sql_mode as m')->m;
        DB::statement("set SESSION sql_mode = ''");

        try {
            $callback();
        } finally {
            DB::statement('set SESSION sql_mode = ?', [$mode]);
        }
    }

    /**
     * Row counts per table, keyed by the table's current unprefixed name.
     *
     * @param  array<int, string>  $tables
     * @return array<string, int>
     */
    protected function rowCounts(array $tables): array
    {
        $counts = [];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $counts[$table] = (int) DB::table($table)->count();
            }
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $before  counts keyed by OLD name
     * @param  array<string, string>  $renames  old name => new name (identity for tables that kept their name)
     */
    protected function assertSameRowCounts(array $before, array $renames = []): void
    {
        foreach ($before as $old => $count) {
            $now = (int) DB::table($renames[$old] ?? $old)->count();
            if ($now !== $count) {
                throw new RuntimeException("Row count changed for {$old}: {$count} before, {$now} after. Aborting; restore the backup.");
            }
        }
    }

    /** @return string e.g. "int(11) unsigned" */
    protected function columnType(string $table, string $column): string
    {
        $row = DB::selectOne(
            'select column_type as t from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
            [$this->t($table), $column]
        );

        return $row->t;
    }

    protected function isNullable(string $table, string $column): bool
    {
        $row = DB::selectOne(
            'select is_nullable as n from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
            [$this->t($table), $column]
        );

        return $row->n === 'YES';
    }
}
