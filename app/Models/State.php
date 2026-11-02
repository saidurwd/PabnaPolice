<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $country_id
 * @property string $title
 * @property string|null $state_2_code
 * @property string|null $state_3_code
 * @property Status|null $status
 */
class State extends BaseModel
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['status' => Status::class];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
