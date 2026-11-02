<?php

namespace App\Jobs;

use App\Models\Visitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Writes one `visitor` row per admin page view (what Yii's BackEndController::statistics() did). */
class RecordAdminVisit implements ShouldQueue
{
    use Queueable;

    /** @param  array<string, mixed>  $visit */
    public function __construct(public array $visit) {}

    public function handle(): void
    {
        Visitor::create($this->visit);
    }
}
