<?php

namespace App\Support\Ai;

use App\Support\Ai\Contracts\AiProviderClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenRouterClient implements AiProviderClient
{
    private const BASE_URL = 'https://openrouter.ai/api/v1';

    public function fetchModels(string $apiKey): array
    {
        $response = Http::withHeaders($this->headers($apiKey))
            ->timeout(20)
            ->get(self::BASE_URL.'/models');

        $response->throw();

        return collect($response->json('data', []))
            ->map(fn (array $model): array => [
                'id' => (string) ($model['id'] ?? ''),
                'name' => (string) ($model['name'] ?? $model['id'] ?? ''),
                'description' => filled($model['description'] ?? null)
                    ? Str::limit(strip_tags((string) $model['description']), 120)
                    : null,
            ])
            ->filter(fn (array $model): bool => filled($model['id']))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function testConnection(string $apiKey, string $modelId): array
    {
        return $this->sendChat($apiKey, $modelId, [
            [
                'role' => 'user',
                'content' => 'Reply with exactly: OK',
            ],
        ], 8);
    }

    public function sendChat(
        string $apiKey,
        string $modelId,
        array $messages,
        int $maxTokens = 300,
        bool $jsonObject = false,
    ): array {
        try {
            $payload = [
                'model' => $modelId,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => 0.4,
            ];

            if ($jsonObject) {
                $payload['response_format'] = ['type' => 'json_object'];
            }

            $response = Http::withHeaders($this->headers($apiKey))
                ->timeout(120)
                ->post(self::BASE_URL.'/chat/completions', $payload);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $this->extractErrorMessage($response->json(), $response->status()),
                    'response' => null,
                    'http_status' => $response->status(),
                ];
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

            if ($content === '') {
                return [
                    'success' => false,
                    'message' => 'OpenRouter returned an empty reply.',
                    'response' => null,
                    'http_status' => $response->status(),
                ];
            }

            return [
                'success' => true,
                'message' => 'AI replied.',
                'response' => $content,
                'http_status' => $response->status(),
            ];
        } catch (RequestException $exception) {
            return [
                'success' => false,
                'message' => $this->extractErrorMessage(
                    $exception->response?->json(),
                    $exception->response?->status() ?? 500,
                ),
                'response' => null,
                'http_status' => $exception->response?->status(),
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'response' => null,
                'http_status' => null,
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $apiKey): array
    {
        return [
            'Authorization' => 'Bearer '.$apiKey,
            'HTTP-Referer' => (string) config('app.url', 'http://127.0.0.1'),
            'X-Title' => (string) config('app.name', 'Desk'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractErrorMessage(?array $payload, int $status): string
    {
        return (string) (data_get($payload, 'error.message')
            ?? data_get($payload, 'message')
            ?? "OpenRouter request failed (HTTP {$status}).");
    }
}
