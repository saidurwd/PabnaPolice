<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Base for the site's models: `id` can't be mass-assigned. Reference tables switch timestamps off. */
abstract class BaseModel extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
}
