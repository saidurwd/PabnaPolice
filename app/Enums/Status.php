<?php

namespace App\Enums;

enum Status: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';

    public function label(): string
    {
        return $this->value;
    }
}
