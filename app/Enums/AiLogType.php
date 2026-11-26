<?php

namespace App\Enums;

enum AiLogType: string
{
    case ConnectionTest = 'connection_test';
    case Prompt = 'prompt';
    case ModelFetch = 'model_fetch';
    case Enrichment = 'enrichment';
    case ForexAssist = 'forex_assist';
    case ForexAnalysis = 'forex_analysis';

    public function label(): string
    {
        return match ($this) {
            self::ConnectionTest => 'Connection test',
            self::Prompt => 'Prompt',
            self::ModelFetch => 'Model fetch',
            self::Enrichment => 'Enrichment',
            self::ForexAssist => 'Forex assist',
            self::ForexAnalysis => 'Forex analysis',
        };
    }
}
