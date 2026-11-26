<?php

namespace App\Support\Ai;

use App\Enums\AiLogType;
use App\Models\AiConfiguration;

/**
 * One entry point for Contacts enrichment, Forex assist, and future AI features.
 */
class DeskAi
{
    public function __construct(
        private readonly AiConfigurationService $configurations,
        private readonly AiProviderRegistry $registry,
        private readonly AiLogService $logger,
    ) {}

    public function ready(): bool
    {
        $configuration = $this->configurations->active();

        return $configuration !== null
            && ! $configuration->isPaused()
            && $configuration->hasUsableApiKey()
            && filled($configuration->model_id);
    }

    public function activeConfiguration(): ?AiConfiguration
    {
        return $this->configurations->active();
    }

    /**
     * @return array{success: bool, message: string, response: string|null, http_status?: int|null}
     */
    public function ask(string $prompt, string $source = 'system', AiLogType $type = AiLogType::Prompt, int $maxTokens = 400): array
    {
        return $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], $source, $type, $maxTokens);
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return array{success: bool, message: string, response: string|null, http_status?: int|null}
     */
    public function chat(
        array $messages,
        string $source = 'system',
        AiLogType $type = AiLogType::Prompt,
        int $maxTokens = 400,
        bool $jsonObject = false,
    ): array {
        $configuration = $this->configurations->active();

        if ($configuration === null) {
            return [
                'success' => false,
                'message' => 'AI is not connected. Open Settings → AI and save an OpenRouter key + model.',
                'response' => null,
            ];
        }

        if ($configuration->isPaused()) {
            $result = [
                'success' => false,
                'message' => 'AI is paused in Settings.',
                'response' => null,
            ];

            $this->logger->fromConfiguration(
                $configuration,
                $type,
                $source,
                false,
                $this->promptPreview($messages),
                null,
                $result['message'],
            );

            return $result;
        }

        $start = microtime(true);
        $result = $this->registry->client($configuration->provider)->sendChat(
            (string) $configuration->api_key,
            $configuration->model_id,
            $messages,
            $maxTokens,
            $jsonObject,
        );
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $this->logger->fromConfiguration(
            $configuration,
            $type,
            $source,
            $result['success'],
            $this->promptPreview($messages),
            $result['response'] ?? null,
            $result['success'] ? null : $result['message'],
            $result['http_status'] ?? null,
            $durationMs,
        );

        return $result;
    }

    /**
     * Forex helpers can call this without caring about providers.
     *
     * @return array{success: bool, message: string, response: string|null, http_status?: int|null}
     */
    public function forexAssist(string $prompt, string $source = 'forex'): array
    {
        return $this->ask($prompt, $source, AiLogType::ForexAssist, 600);
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    private function promptPreview(array $messages): string
    {
        $chunks = [];

        foreach ($messages as $message) {
            $content = $message['content'] ?? '';

            if (is_string($content)) {
                $chunks[] = $content;

                continue;
            }

            if (is_array($content)) {
                foreach ($content as $part) {
                    if (is_array($part) && ($part['type'] ?? null) === 'text') {
                        $chunks[] = (string) ($part['text'] ?? '');
                    }
                }
            }
        }

        return mb_substr(trim(implode("\n", array_filter($chunks))), 0, 4000);
    }
}
