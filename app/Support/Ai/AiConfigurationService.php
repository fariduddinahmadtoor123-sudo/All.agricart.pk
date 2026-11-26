<?php

namespace App\Support\Ai;

use App\Enums\AiLogStatus;
use App\Enums\AiLogType;
use App\Enums\AiProvider;
use App\Models\AiConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiConfigurationService
{
    public function __construct(
        private readonly AiProviderRegistry $registry,
        private readonly AiLogService $logger,
    ) {}

    public function active(): ?AiConfiguration
    {
        $configuration = AiConfiguration::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($configuration === null) {
            return $this->fromEnvFallback();
        }

        if ($configuration->hasCorruptApiKey()) {
            $this->markApiKeyUnreadable($configuration);

            return $this->fromEnvFallback();
        }

        if (! $configuration->hasUsableApiKey()) {
            return $this->fromEnvFallback();
        }

        return $configuration;
    }

    /**
     * Optional published-app fallback. Local Desk still prefers the Settings form.
     */
    private function fromEnvFallback(): ?AiConfiguration
    {
        $key = (string) env('OPENROUTER_API_KEY', '');
        $model = (string) env('OPENROUTER_MODEL', '');

        if ($key === '' || $model === '') {
            return null;
        }

        $configuration = new AiConfiguration([
            'provider' => AiProvider::OpenRouter,
            'api_key' => $key,
            'model_id' => $model,
            'model_name' => $model,
            'is_active' => true,
            'connection_status' => 'connected',
        ]);
        $configuration->exists = false;

        return $configuration;
    }

    public function latest(): ?AiConfiguration
    {
        return AiConfiguration::query()->latest('id')->first();
    }

    /**
     * @param  array{provider: string, api_key?: string|null, model_id: string, model_name?: string|null}  $data
     */
    public function save(array $data, ?AiConfiguration $existing = null): AiConfiguration
    {
        $provider = AiProvider::from($data['provider']);

        return DB::transaction(function () use ($data, $provider, $existing): AiConfiguration {
            AiConfiguration::query()->update(['is_active' => false]);

            $payload = [
                'provider' => $provider,
                'model_id' => $data['model_id'],
                'model_name' => $data['model_name'] ?? null,
                'is_active' => true,
                'connection_status' => 'untested',
                'last_test_message' => null,
                'last_tested_at' => null,
            ];

            if (filled($data['api_key'] ?? null)) {
                $payload['api_key'] = $data['api_key'];
            } elseif ($existing === null || ! $existing->hasUsableApiKey()) {
                throw ValidationException::withMessages([
                    'api_key' => 'Paste your OpenRouter API key.',
                ]);
            }

            if ($existing !== null) {
                $existing->update($payload);
                $configuration = $existing->fresh();
            } else {
                if (! isset($payload['api_key'])) {
                    throw ValidationException::withMessages([
                        'api_key' => 'Paste your OpenRouter API key.',
                    ]);
                }

                $configuration = AiConfiguration::query()->create($payload);
            }

            $this->applyConnectionTest($configuration, 'settings_save');

            return $configuration->fresh();
        });
    }

    public function testConfiguration(AiConfiguration $configuration, string $source = 'settings'): AiConfiguration
    {
        $this->applyConnectionTest($configuration, $source);

        return $configuration->fresh();
    }

    public function pause(AiConfiguration $configuration): AiConfiguration
    {
        $configuration->update([
            'connection_status' => 'paused',
            'last_test_message' => 'AI is paused. Resume when you want Desk to use it again.',
        ]);

        return $configuration->fresh();
    }

    public function resume(AiConfiguration $configuration): AiConfiguration
    {
        $this->applyConnectionTest($configuration, 'settings_resume');

        return $configuration->fresh();
    }

    /**
     * @return list<array{id: string, name: string, description: string|null}>
     */
    public function fetchModels(AiProvider $provider, string $apiKey, string $source = 'settings_form'): array
    {
        $start = microtime(true);

        try {
            $models = $this->registry->client($provider)->fetchModels($apiKey);
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $this->logger->record([
                'provider' => $provider->value,
                'type' => AiLogType::ModelFetch,
                'status' => AiLogStatus::Success,
                'source' => $source,
                'duration_ms' => $durationMs,
                'metadata' => ['model_count' => count($models)],
            ]);

            return $models;
        } catch (\Throwable $exception) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $this->logger->record([
                'provider' => $provider->value,
                'type' => AiLogType::ModelFetch,
                'status' => AiLogStatus::Error,
                'source' => $source,
                'duration_ms' => $durationMs,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return list<array{id: string, name: string, description: string|null}>
     */
    public function cachedModelsFor(int $userId): array
    {
        $models = Cache::get($this->modelsCacheKey($userId), []);

        return is_array($models) ? $models : [];
    }

    /**
     * @param  list<array{id: string, name: string, description: string|null}>  $models
     */
    public function rememberModels(int $userId, array $models): void
    {
        Cache::put($this->modelsCacheKey($userId), $models, now()->addMinutes(30));
    }

    public function forgetModels(int $userId): void
    {
        Cache::forget($this->modelsCacheKey($userId));
    }

    private function modelsCacheKey(int $userId): string
    {
        return "desk.ai.models.{$userId}";
    }

    private function markApiKeyUnreadable(AiConfiguration $configuration): void
    {
        $configuration->forceFill([
            'api_key' => '',
            'is_active' => false,
            'connection_status' => 'failed',
            'last_test_message' => 'API key could not be read. Paste it again in Settings → AI.',
            'last_tested_at' => now(),
        ])->save();
    }

    private function applyConnectionTest(AiConfiguration $configuration, string $source): void
    {
        $start = microtime(true);
        $result = $this->registry->client($configuration->provider)->testConnection(
            (string) $configuration->api_key,
            $configuration->model_id,
        );
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $this->logger->fromConfiguration(
            $configuration,
            AiLogType::ConnectionTest,
            $source,
            $result['success'],
            'Reply with exactly: OK',
            $result['response'] ?? null,
            $result['success'] ? null : $result['message'],
            $result['http_status'] ?? null,
            $durationMs,
        );

        $configuration->update([
            'connection_status' => $result['success'] ? 'connected' : 'failed',
            'last_test_message' => $result['message'],
            'last_tested_at' => now(),
        ]);
    }
}
