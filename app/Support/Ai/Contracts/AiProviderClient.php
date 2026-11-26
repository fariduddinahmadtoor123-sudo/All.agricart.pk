<?php

namespace App\Support\Ai\Contracts;

interface AiProviderClient
{
    /**
     * @return list<array{id: string, name: string, description: string|null}>
     */
    public function fetchModels(string $apiKey): array;

    /**
     * @return array{success: bool, message: string, response?: string|null, http_status?: int|null}
     */
    public function testConnection(string $apiKey, string $modelId): array;

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return array{success: bool, message: string, response: string|null, http_status?: int|null}
     */
    public function sendChat(
        string $apiKey,
        string $modelId,
        array $messages,
        int $maxTokens = 300,
        bool $jsonObject = false,
    ): array;
}
