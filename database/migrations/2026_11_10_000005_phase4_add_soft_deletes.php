<?php

use App\Support\BreakingMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 4, step 5: deleting content, banners or documents in the admin moves them to a trash that can be restored. */
return new class extends BreakingMigration
{
    public function up(): void
    {
        $this->ensureYiiRetired();

        foreach (['contents', 'banners', 'documents'] as $table) {
            if (! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                    $t->index('deleted_at');
                });
            }
        }
    }

    public function down(): void
    {
        // Additive; nothing to undo safely.
    }
};
