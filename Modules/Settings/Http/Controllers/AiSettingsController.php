<?php

namespace Modules\Settings\Http\Controllers;

use App\Enums\AiProvider;
use App\Http\Controllers\Controller;
use App\Support\Ai\AiConfigurationService;
use App\Support\Ai\AiProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function loadModels(Request $request, AiConfigurationService $configurations): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'api_key' => ['nullable', 'string', 'min:8'],
        ]);

        $provider = AiProvider::from($data['provider']);
        $apiKey = $data['api_key'] ?? null;

        if (! filled($apiKey)) {
            $existing = $configurations->latest();
            $apiKey = $existing?->api_key;
        }

        if (! filled($apiKey)) {
            return back()->withInput()->withErrors([
                'api_key' => 'Paste your OpenRouter API key to load models.',
            ]);
        }

        try {
            $models = $configurations->fetchModels($provider, $apiKey);
            $configurations->rememberModels((int) $request->user()->id, $models);
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors([
                'api_key' => $exception->getMessage(),
            ]);
        }

        return back()->withInput()->with('status', count($models).' models loaded from OpenRouter. Pick one and save.');
    }

    public function store(Request $request, AiConfigurationService $configurations): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'api_key' => ['nullable', 'string', 'min:8'],
            'model_id' => ['required', 'string', 'max:191'],
            'model_name' => ['nullable', 'string', 'max:191'],
        ]);

        $existing = $configurations->latest();
        $configuration = $configurations->save($data, $existing);

        $configurations->forgetModels((int) $request->user()->id);

        $status = $configuration->isConnected()
            ? 'AI connected: '.$configuration->displayName()
            : 'Saved, but connection test failed. Check the key/model.';

        return redirect()->route('settings.ai')->with('status', $status);
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
}
