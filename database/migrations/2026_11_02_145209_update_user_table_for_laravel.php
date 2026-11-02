<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: adds the "remember me" token column and relaxes `photo` (NOT NULL without
 * default in the Yii schema) so users can be created without a photo. No data is modified.
 *
 * The live `user` table has `DEFAULT '0000-00-00 00:00:00'` on register_date/lastvisit, which
 * strict MySQL (NO_ZERO_DATE) refuses to ALTER, so the SQL mode is relaxed for this session only.
 */
return new class extends Migration
{
    public function up(): void
    {
        $mode = DB::selectOne('select @@SESSION.sql_mode as m')->m;
        DB::statement("set SESSION sql_mode = ''");

        try {
            $alter = [];
            if (! Schema::hasColumn('user', 'remember_token')) {
                $alter[] = 'ADD COLUMN `remember_token` VARCHAR(100) NULL';
            }
            $alter[] = 'MODIFY COLUMN `photo` VARCHAR(255) NULL DEFAULT NULL';

            DB::statement('ALTER TABLE `'.DB::getTablePrefix().'user` '.implode(', ', $alter));
        } finally {
            DB::statement('set SESSION sql_mode = ?', [$mode]);
        }
    }

    public function down(): void
    {
        // Additive change; nothing to undo safely.
    }
};
