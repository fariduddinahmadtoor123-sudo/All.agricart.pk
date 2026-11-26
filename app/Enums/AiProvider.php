<?php

namespace App\Enums;

enum AiProvider: string
{
    case OpenRouter = 'openrouter';
    case OpenAi = 'openai';
    case Anthropic = 'anthropic';
    case Google = 'google';

    public function label(): string
    {
        return match ($this) {
            self::OpenRouter => 'OpenRouter',
            self::OpenAi => 'OpenAI',
            self::Anthropic => 'Anthropic',
            self::Google => 'Google',
        };
    }

    public function isEnabled(): bool
    {
        return $this === self::OpenRouter;
    }

    /**
     * @return list<self>
     */
    public static function selectable(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $provider): bool => $provider->isEnabled(),
        ));
    }
}
