<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: three nullable columns for optional two-factor authentication.
 *
 * Same caveat as update_user_table_for_laravel: the live `user` table has zero-date defaults that strict
 * MySQL refuses to ALTER, so the SQL mode is relaxed for this session only. No existing data is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $mode = DB::selectOne('select @@SESSION.sql_mode as m')->m;
        DB::statement("set SESSION sql_mode = ''");

        try {
            $add = [];
            foreach ([
                'two_factor_secret' => 'TEXT NULL',
                'two_factor_recovery_codes' => 'TEXT NULL',
                'two_factor_confirmed_at' => 'TIMESTAMP NULL DEFAULT NULL',
            ] as $column => $definition) {
                if (! Schema::hasColumn('user', $column)) {
                    $add[] = "ADD COLUMN `{$column}` {$definition}";
                }
            }

            if ($add) {
                DB::statement('ALTER TABLE `'.DB::getTablePrefix().'user` '.implode(', ', $add));
            }
        } finally {
            DB::statement('set SESSION sql_mode = ?', [$mode]);
        }
    }

    public function down(): void
    {
        // Additive change; nothing to undo safely.
    }
};
