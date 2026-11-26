<?php

namespace App\Support\Ai;

class RecommendedModels
{
    public const PREFERRED = 'deepseek/deepseek-v4.1-flash';

    /**
     * Cheap OpenRouter models that still answer well. The full provider list stays available.
     *
     * @return list<array{id: string, name: string, note: string}>
     */
    public static function all(): array
    {
        return [
            [
                'id' => self::PREFERRED,
                'name' => 'DeepSeek V4.1 Flash',
                'note' => 'Best cheap pick. Desk selects this when OpenRouter offers it.',
            ],
            [
                'id' => 'qwen/qwen3.7-flash',
                'name' => 'Qwen3.7 Flash',
                'note' => 'Cheapest flash that still answers clearly.',
            ],
            [
                'id' => 'qwen/qwen3.5-flash-02-23',
                'name' => 'Qwen3.5 Flash',
                'note' => 'Still cheap, steady for short Desk text.',
            ],
            [
                'id' => 'openai/gpt-4o-mini',
                'name' => 'GPT-4o mini',
                'note' => 'A little more, and very reliable.',
            ],
            [
                'id' => 'anthropic/claude-haiku-5.5',
                'name' => 'Claude Haiku 5.5',
                'note' => 'Clearest writing of the cheap set.',
            ],
        ];
    }
}
