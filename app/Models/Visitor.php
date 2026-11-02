<?php

namespace App\Models;

/**
 * @property int $id
 * @property int|null $user_type
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string|null $page_title
 * @property string|null $page_link
 * @property string|null $server_time
 * @property string|null $browser
 * @property string|null $visitor_ip
 */
class Visitor extends BaseModel
{
    public $timestamps = false;
}
