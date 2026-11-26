<?php

namespace App\Support\Ai;

class AiFailure
{
    public static function isOutOfCredit(string $message, ?int $status = null): bool
    {
        if ($status === 402) {
            return true;
        }

        $text = strtolower($message);

        return str_contains($text, 'credit')
            || str_contains($text, 'insufficient')
            || str_contains($text, 'quota')
            || str_contains($text, 'billing');
    }
}
