<?php

namespace Tests\Feature;

use App\Enums\AiLogType;
use App\Enums\AiProvider;
use App\Models\AiConfiguration;
use App\Models\AiLog;
use App\Models\User;
use App\Support\Ai\DeskAi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_settings_page_loads_for_super_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/settings/ai')
            ->assertOk()
            ->assertSee('AI settings')
            ->assertSee('OpenRouter')
            ->assertSee('Load models')
            ->assertSee('Save & test', false);
    }

    public function test_models_can_be_loaded_from_openrouter(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    ['id' => 'openai/gpt-4o-mini', 'name' => 'GPT-4o Mini', 'description' => 'Fast'],
                    ['id' => 'anthropic/claude-3.5-sonnet', 'name' => 'Claude 3.5 Sonnet'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/settings/ai')
            ->post('/settings/ai/models', [
                'provider' => 'openrouter',
                'api_key' => 'sk-or-test-key-123456',
            ])
            ->assertRedirect('/settings/ai')
            ->assertSessionHas('status');

        $this->actingAs($user)->get('/settings/ai')
            ->assertOk()
            ->assertSee('GPT-4o Mini')
            ->assertSee('Claude 3.5 Sonnet');

        $this->assertDatabaseHas('ai_logs', [
            'type' => AiLogType::ModelFetch->value,
            'status' => 'success',
        ]);
    }

    public function test_configuration_can_be_saved_and_tested(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'OK']],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/ai', [
                'provider' => 'openrouter',
                'api_key' => 'sk-or-test-key-123456',
                'model_id' => 'openai/gpt-4o-mini',
                'model_name' => 'GPT-4o Mini',
            ])
            ->assertRedirect('/settings/ai')
            ->assertSessionHas('status');

        $configuration = AiConfiguration::query()->firstOrFail();

        $this->assertTrue($configuration->is_active);
        $this->assertTrue($configuration->isConnected());
        $this->assertSame('openai/gpt-4o-mini', $configuration->model_id);
        $this->assertSame('sk-or-test-key-123456', $configuration->api_key);
        $this->assertNotSame('sk-or-test-key-123456', $configuration->getRawOriginal('api_key'));

        $this->assertDatabaseHas('ai_logs', [
            'type' => AiLogType::ConnectionTest->value,
            'status' => 'success',
            'model_id' => 'openai/gpt-4o-mini',
        ]);

        $this->actingAs($user)->get('/settings/ai-logs')
            ->assertOk()
            ->assertSee('Connection test')
            ->assertSee('GPT-4o Mini');
    }

    public function test_desk_ai_ask_uses_saved_configuration_and_logs(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'EURUSD bias: bullish']],
                ],
            ], 200),
        ]);

        AiConfiguration::query()->create([
            'provider' => AiProvider::OpenRouter,
            'api_key' => 'sk-or-test-key-123456',
            'model_id' => 'openai/gpt-4o-mini',
            'model_name' => 'GPT-4o Mini',
            'is_active' => true,
            'connection_status' => 'connected',
        ]);

        $result = app(DeskAi::class)->forexAssist('Summarize EURUSD for today.');

        $this->assertTrue($result['success']);
        $this->assertSame('EURUSD bias: bullish', $result['response']);

        $this->assertDatabaseHas('ai_logs', [
            'type' => AiLogType::ForexAssist->value,
            'status' => 'success',
            'source' => 'forex',
        ]);

        $this->assertTrue(app(DeskAi::class)->ready());
    }

    public function test_pause_blocks_desk_ai_until_resume(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'OK']],
                ],
            ], 200),
        ]);

        AiConfiguration::query()->create([
            'provider' => AiProvider::OpenRouter,
            'api_key' => 'sk-or-test-key-123456',
            'model_id' => 'openai/gpt-4o-mini',
            'model_name' => 'GPT-4o Mini',
            'is_active' => true,
            'connection_status' => 'connected',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/settings/ai')
            ->post('/settings/ai/pause')
            ->assertRedirect('/settings/ai');

        $this->assertTrue(AiConfiguration::query()->firstOrFail()->isPaused());
        $this->assertFalse(app(DeskAi::class)->ready());

        $paused = app(DeskAi::class)->ask('Hello');
        $this->assertFalse($paused['success']);
        $this->assertStringContainsString('paused', $paused['message']);

        $this->actingAs($user)
            ->from('/settings/ai')
            ->post('/settings/ai/resume')
            ->assertRedirect('/settings/ai');

        $this->assertTrue(AiConfiguration::query()->firstOrFail()->isConnected());
        $this->assertTrue(app(DeskAi::class)->ready());
    }

    public function test_failed_openrouter_response_is_logged(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'error' => ['message' => 'Invalid API key'],
            ], 401),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/ai', [
                'provider' => 'openrouter',
                'api_key' => 'sk-or-bad-key-123456',
                'model_id' => 'openai/gpt-4o-mini',
                'model_name' => 'GPT-4o Mini',
            ])
            ->assertRedirect('/settings/ai');

        $configuration = AiConfiguration::query()->firstOrFail();
        $this->assertSame('failed', $configuration->connection_status);
        $this->assertStringContainsString('Invalid API key', (string) $configuration->last_test_message);

        $this->assertDatabaseHas('ai_logs', [
            'type' => AiLogType::ConnectionTest->value,
            'status' => 'error',
        ]);
    }
}
