<?php

use App\Support\BreakingMigration;
use Illuminate\Support\Facades\Schema;

/** Phase 4, step 6: the Yii app stored its login sessions here. Nothing in Laravel reads it (sessions are files). */
return new class extends BreakingMigration
{
    public function up(): void
    {
        $this->ensureYiiRetired();

        Schema::dropIfExists('yiisession');
    }

    public function down(): void
    {
        // Not recreated: it only ever held expired Yii login sessions.
    }
};
