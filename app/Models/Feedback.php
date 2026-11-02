<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $nid
 * @property string|null $subject
 * @property string $message
 * @property string|null $ip
 * @property bool $is_read
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Feedback extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }
}
