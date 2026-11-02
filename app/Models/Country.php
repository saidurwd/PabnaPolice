<?php

namespace App\Models;

use App\Enums\Status;

/**
 * @property int $id
 * @property string $title
 * @property string|null $country_2_code
 * @property string|null $country_3_code
 * @property Status|null $status
 */
class Country extends BaseModel
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['status' => Status::class];
    }
}
