<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $country_id
 * @property int $state_id
 * @property string $title
 * @property string|null $city_2_code
 * @property string|null $city_3_code
 * @property Status|null $status
 */
class City extends BaseModel
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

    /** @return BelongsTo<State, $this> */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
