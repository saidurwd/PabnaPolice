<?php

namespace App\Models;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $login_time
 * @property string|null $logout_time
 */
class AuditTrail extends BaseModel
{
    public $timestamps = false;
}
