<?php

namespace App\Enums;

enum AiLogStatus: string
{
    case Success = 'success';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Success',
            self::Error => 'Error',
        };
    }
}
