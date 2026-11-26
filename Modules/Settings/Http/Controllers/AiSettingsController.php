<?php

namespace Modules\Settings\Http\Controllers;

use App\Enums\AiLogStatus;
use App\Enums\AiLogType;
use App\Enums\AiProvider;
use App\Http\Controllers\Controller;
use App\Models\AiLog;
use App\Support\Ai\AiConfigurationService;
use App\Support\Ai\AiLogService;
use App\Support\Ai\AiProviderRegistry;
use App\Support\Ai\DeskAi;
use App\Support\Ai\OpenRouterClient;
use App\Support\Ai\RecommendedModels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function __invoke(
        Request $request,
        AiConfigurationService $configurations,
        AiProviderRegistry $registry,
    ): View {
        $user = $request->user();
        $active = $configurations->latest();

        return view('settings.ai', [
            'user' => $user,
            'configuration' => $active,
            'providers' => $registry->providerOptions(),
            'models' => $configurations->cachedModelsFor((int) $user->id),
            'recommended' => RecommendedModels::all(),
            'preferredModelId' => RecommendedModels::PREFERRED,
            'keyReady' => $request->session()->has('desk.ai.draft_key'),
            'logs' => AiLog::query()->latest('id')->limit(50)->get(),
        ]);
    }

    public function loadModels(Request $request, AiConfigurationService $configurations): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'api_key' => ['nullable', 'string', 'min:8'],
        ]);

        $provider = AiProvider::from($data['provider']);
        $apiKey = $this->resolveKey($request, $data['api_key'] ?? null, $configurations);

        if (! filled($apiKey)) {
            return $this->backToForm($request)->withErrors([
                'api_key' => 'Paste your OpenRouter API key to load models.',
            ]);
        }

        try {
            $models = $configurations->fetchModels($provider, $apiKey);
            $configurations->rememberModels((int) $request->user()->id, $models);
        } catch (\Throwable $exception) {
            return $this->backToForm($request)->withErrors([
                'api_key' => $exception->getMessage(),
            ]);
        }

        return $this->backToForm($request)
            ->with('status', count($models).' models loaded. Your pick shows above. The cheap one to keep is DeepSeek V4.1 Flash.');
    }

    public function store(Request $request, AiConfigurationService $configurations): RedirectResponse
    {
        $request->merge([
            'api_key' => $this->resolveKey($request, $request->input('api_key'), $configurations),
        ]);

        $data = $request->validate([
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'api_key' => ['nullable', 'string', 'min:8'],
            'model_id' => ['required', 'string', 'max:191'],
            'model_name' => ['nullable', 'string', 'max:191'],
        ]);

        $existing = $configurations->latest();
        $configuration = $configurations->save($data, $existing);
        $request->session()->forget('desk.ai.draft_key');

        $configurations->forgetModels((int) $request->user()->id);

        $status = match (true) {
            $configuration->isConnected() => 'AI connected: '.$configuration->displayName(),
            $configuration->isOutOfCredit() => 'AI credits are finished. '.$configuration->last_test_message,
            default => 'Saved, but the test failed. '.$configuration->last_test_message,
        };

        return redirect()->route('settings.ai')->with('status', $status);
    }

    public function ask(Request $request, DeskAi $deskAi): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $result = $deskAi->ask($data['question'], 'settings_test', AiLogType::Prompt, 400);

        return back()
            ->withInput($request->only('question'))
            ->with('ai_answer', $result['response'])
            ->with('status', $result['success'] ? 'AI replied.' : $result['message']);
    }

    public function askDraft(
        Request $request,
        AiConfigurationService $configurations,
        OpenRouterClient $openRouter,
        AiLogService $logger,
    ): RedirectResponse {
        $data = $request->validate([
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'api_key' => ['nullable', 'string', 'min:8'],
            'model_id' => ['required', 'string', 'max:191'],
            'model_name' => ['nullable', 'string', 'max:191'],
            'question' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $apiKey = $this->resolveKey($request, $data['api_key'] ?? null, $configurations);

        if (! filled($apiKey)) {
            return $this->backToForm($request)->withErrors([
                'api_key' => 'Paste your OpenRouter API key.',
            ]);
        }

        $provider = AiProvider::from($data['provider']);
        $start = microtime(true);
        $result = $openRouter->sendChat($apiKey, $data['model_id'], [
            ['role' => 'user', 'content' => $data['question']],
        ], 400);

        $logger->record([
            'provider' => $provider->value,
            'model_id' => $data['model_id'],
            'model_name' => $data['model_name'] ?? $data['model_id'],
            'type' => AiLogType::Prompt,
            'status' => $result['success'] ? AiLogStatus::Success : AiLogStatus::Error,
            'source' => 'settings_draft',
            'http_status' => $result['http_status'] ?? null,
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            'prompt' => $data['question'],
            'response' => $result['response'] ?? null,
            'error_message' => $result['success'] ? null : $result['message'],
        ]);

        return $this->backToForm($request)
            ->with('ai_draft_answer', $result['response'])
            ->with('status', $result['success'] ? 'AI replied.' : $result['message']);
    }

    public function test(AiConfigurationService $configurations): RedirectResponse
    {
        $configuration = $configurations->latest();

        if ($configuration === null) {
            return back()->withErrors(['api_key' => 'Save an AI connection first.']);
        }

        $configuration = $configurations->testConfiguration($configuration);

        return back()->with(
            'status',
            $configuration->isConnected()
                ? 'Connection OK — '.$configuration->displayName()
                : 'Connection failed: '.$configuration->last_test_message,
        );
    }

    public function pause(AiConfigurationService $configurations): RedirectResponse
    {
        $configuration = $configurations->latest();

        if ($configuration === null) {
            return back()->withErrors(['api_key' => 'Nothing to pause.']);
        }

        $configurations->pause($configuration);

        return back()->with('status', 'AI paused.');
    }

    public function resume(AiConfigurationService $configurations): RedirectResponse
    {
        $configuration = $configurations->latest();

        if ($configuration === null) {
            return back()->withErrors(['api_key' => 'Nothing to resume.']);
        }

        $configuration = $configurations->resume($configuration);

        return back()->with(
            'status',
            $configuration->isConnected() ? 'AI resumed and connected.' : 'Resume tested, still failing.',
        );
    }

    private function resolveKey(Request $request, ?string $typed, AiConfigurationService $configurations): ?string
    {
        if (filled($typed)) {
            $request->session()->put('desk.ai.draft_key', Crypt::encryptString($typed));

            return $typed;
        }

        $stored = $request->session()->get('desk.ai.draft_key');

        if (is_string($stored) && $stored !== '') {
            try {
                $plain = Crypt::decryptString($stored);

                if (is_string($plain) && $plain !== '') {
                    return $plain;
                }
            } catch (\Throwable) {
                $request->session()->forget('desk.ai.draft_key');
            }
        }

        return $configurations->latest()?->api_key;
    }

    private function backToForm(Request $request): RedirectResponse
    {
        return back()
            ->withInput($request->except('api_key'))
            ->with('ai_window', true);
    }
}
